<?php

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\IncidentLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('migrations create the required columns with MySQL compatible types and storage', function () {
    expect(Schema::getColumnListing('incidents'))->toBe([
        'id', 'title', 'description', 'status', 'priority', 'requester_name', 'assigned_to', 'created_at', 'updated_at',
    ]);
    expect(Schema::getColumnListing('incident_logs'))->toBe([
        'id', 'incident_id', 'action', 'old_value', 'new_value', 'user_name', 'metadata', 'created_at',
    ]);
    expect(Schema::getColumnListing('incident_comments'))->toBe([
        'id', 'incident_id', 'author_name', 'body', 'created_at', 'updated_at',
    ]);
    expect(Schema::getColumnType('incidents', 'status', fullDefinition: true))->toBe('varchar(32)');
    expect(Schema::getColumnType('incidents', 'priority', fullDefinition: true))->toBe('varchar(16)');
    expect(Schema::getColumnType('incident_logs', 'metadata'))->toBe('json');

    $tables = DB::table('information_schema.tables')
        ->where('table_schema', DB::connection()->getDatabaseName())
        ->whereIn('table_name', ['incidents', 'incident_logs', 'incident_comments'])
        ->get(['ENGINE', 'TABLE_COLLATION']);

    expect($tables)->toHaveCount(3);
    foreach ($tables as $table) {
        expect($table->ENGINE)->toBe('InnoDB');
        expect($table->TABLE_COLLATION)->toBe('utf8mb4_unicode_ci');
    }
});

test('migrations index the list filters and related timelines without duplicate foreign key indexes', function () {
    $incidentIndexes = collect(Schema::getIndexes('incidents'))->pluck('columns')->all();

    expect($incidentIndexes)->toContain(['created_at'], ['status', 'created_at'], ['priority', 'created_at']);

    foreach (['incident_logs', 'incident_comments'] as $table) {
        $indexes = collect(Schema::getIndexes($table))->pluck('columns')->all();

        expect($indexes)->toContain(['incident_id', 'created_at']);
        expect($indexes)->not->toContain(['incident_id']);
    }
});

test('incidents use database defaults and allow an optional responsible name', function () {
    $incident = Incident::query()->create([
        'title' => 'Printer unavailable',
        'description' => 'The receipt printer does not respond.',
        'requester_name' => 'Demo requester',
    ])->refresh();

    expect($incident->status)->toBe(IncidentStatus::Open);
    expect($incident->priority)->toBe(IncidentPriority::Medium);
    expect($incident->assigned_to)->toBeNull();

    $incident->assigned_to = 'Demo operator';
    $incident->save();

    expect($incident->fresh()->assigned_to)->toBe('Demo operator');
});

test('incident enums persist as strings and are restored as enum cases', function (
    IncidentStatus $status,
    string $storedStatus,
    IncidentPriority $priority,
    string $storedPriority,
) {
    $incident = Incident::factory()->create(['status' => $status, 'priority' => $priority])->refresh();

    expect($incident->status)->toBe($status);
    expect($incident->priority)->toBe($priority);
    $this->assertDatabaseHas('incidents', [
        'id' => $incident->id,
        'status' => $storedStatus,
        'priority' => $storedPriority,
    ]);
})->with([
    'open and low' => [IncidentStatus::Open, 'open', IncidentPriority::Low, 'low'],
    'in review and medium' => [IncidentStatus::InReview, 'in_review', IncidentPriority::Medium, 'medium'],
    'blocked and high' => [IncidentStatus::Blocked, 'blocked', IncidentPriority::High, 'high'],
    'resolved and low' => [IncidentStatus::Resolved, 'resolved', IncidentPriority::Low, 'low'],
]);

test('incident enum casts reject unknown values', function (string $attribute) {
    expect(fn () => new Incident([$attribute => 'unknown']))->toThrow(ValueError::class);
})->with(['status', 'priority']);

test('related records belong to their incident and use chronological order with an id tie breaker', function (
    string $modelClass,
    string $relationship,
) {
    $this->freezeSecond();
    $incident = Incident::factory()->create();
    $first = $modelClass::factory()->for($incident)->create();
    $older = $modelClass::factory()->for($incident)->create(['created_at' => now()->subHour()]);
    $tied = $modelClass::factory()->for($incident)->create();
    $modelClass::factory()->create();

    $records = $incident->{$relationship};

    expect($records->modelKeys())->toBe([$older->id, $first->id, $tied->id]);
    expect($first->incident->is($incident))->toBeTrue();
})->with([
    'logs' => [IncidentLog::class, 'logs'],
    'comments' => [IncidentComment::class, 'comments'],
]);

test('related records cannot reference a missing incident', function (string $modelClass) {
    expect(fn () => $modelClass::factory()->create(['incident_id' => 999999]))
        ->toThrow(QueryException::class, '1452');
})->with([IncidentLog::class, IncidentComment::class]);

test('incidents with related records cannot be deleted', function (string $modelClass) {
    $incident = Incident::factory()->create();
    $related = $modelClass::factory()->for($incident)->create();

    expect(fn () => $incident->delete())->toThrow(QueryException::class, '1451');

    $this->assertModelExists($incident);
    $this->assertModelExists($related);
})->with([IncidentLog::class, IncidentComment::class]);

test('log metadata round trips through JSON while previous and new values can be null', function () {
    $log = IncidentLog::factory()->create([
        'action' => 'diagnostic_note',
        'old_value' => null,
        'new_value' => null,
        'metadata' => ['source' => 'manual', 'details' => ['review_required' => true]],
    ])->refresh();

    expect($log->metadata)->toEqual(['source' => 'manual', 'details' => ['review_required' => true]]);
    expect($log->old_value)->toBeNull();
    expect($log->new_value)->toBeNull();

    $log->metadata = null;
    $log->save();

    $this->assertDatabaseHas('incident_logs', ['id' => $log->id, 'metadata' => null]);
    expect($log->fresh()->metadata)->toBeNull();
});

test('logs automatically keep only their original creation timestamp when saved', function () {
    $this->freezeSecond();
    $log = IncidentLog::factory()->create();
    $createdAt = now();

    $this->travel(1)->hour();
    $log->save();
    $log->refresh();

    expect($log->created_at)->toEqual($createdAt);
    expect($log->getAttributes())->not->toHaveKey('updated_at');
});

test('incidents and comments maintain both timestamps', function (string $modelClass, string $attribute) {
    $this->freezeSecond();
    $model = $modelClass::factory()->create();
    $createdAt = now();

    $this->travel(1)->hour();
    $model->{$attribute} = 'Updated content';
    $model->save();
    $model->refresh();

    expect($model->created_at)->toEqual($createdAt);
    expect($model->updated_at)->toEqual(now());
})->with([
    'incident' => [Incident::class, 'title'],
    'comment' => [IncidentComment::class, 'body'],
]);
