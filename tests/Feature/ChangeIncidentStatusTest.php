<?php

use App\Actions\ChangeIncidentStatus;
use App\Enums\IncidentStatus;
use App\Exceptions\IncidentStatusConflict;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

test('allowed transitions persist the status and its complete audit entry', function (
    IncidentStatus $oldStatus,
    IncidentStatus $newStatus,
) {
    $this->freezeSecond();
    $incident = Incident::factory()->create(['status' => $oldStatus]);
    $this->travel(1)->hour();

    $updated = (new ChangeIncidentStatus)->handle($incident, $newStatus, '  Demo operator  ');

    expect($updated->is($incident))->toBeTrue();
    expect($updated->status)->toBe($newStatus);
    $this->assertDatabaseHas('incidents', [
        'id' => $incident->id,
        'status' => $newStatus->value,
        'updated_at' => now()->toDateTimeString(),
    ]);
    $this->assertDatabaseCount('incident_logs', 1);
    $this->assertDatabaseHas('incident_logs', [
        'incident_id' => $incident->id,
        'action' => 'status_changed',
        'old_value' => $oldStatus->value,
        'new_value' => $newStatus->value,
        'user_name' => 'Demo operator',
        'created_at' => now()->toDateTimeString(),
        'metadata' => null,
    ]);
})->with([
    'open to in review' => [IncidentStatus::Open, IncidentStatus::InReview],
    'in review to blocked' => [IncidentStatus::InReview, IncidentStatus::Blocked],
    'in review to resolved' => [IncidentStatus::InReview, IncidentStatus::Resolved],
]);

test('forbidden transitions leave the incident and its history unchanged', function (
    IncidentStatus $oldStatus,
    IncidentStatus $newStatus,
) {
    $incident = Incident::factory()->create(['status' => $oldStatus]);
    $original = $incident->fresh()->getRawOriginal();

    expect(fn () => (new ChangeIncidentStatus)->handle($incident, $newStatus, 'Demo operator'))
        ->toThrow(IncidentStatusConflict::class, 'El cambio de estado solicitado no está permitido para el estado actual de la incidencia.');

    expect($incident->fresh()->getRawOriginal())->toBe($original);
    $this->assertDatabaseCount('incident_logs', 0);
})->with([
    'open to open' => [IncidentStatus::Open, IncidentStatus::Open],
    'open to blocked' => [IncidentStatus::Open, IncidentStatus::Blocked],
    'open to resolved' => [IncidentStatus::Open, IncidentStatus::Resolved],
    'in review to open' => [IncidentStatus::InReview, IncidentStatus::Open],
    'in review to in review' => [IncidentStatus::InReview, IncidentStatus::InReview],
    'blocked to open' => [IncidentStatus::Blocked, IncidentStatus::Open],
    'blocked to in review' => [IncidentStatus::Blocked, IncidentStatus::InReview],
    'blocked to blocked' => [IncidentStatus::Blocked, IncidentStatus::Blocked],
    'blocked to resolved' => [IncidentStatus::Blocked, IncidentStatus::Resolved],
    'resolved to open' => [IncidentStatus::Resolved, IncidentStatus::Open],
    'resolved to in review' => [IncidentStatus::Resolved, IncidentStatus::InReview],
    'resolved to blocked' => [IncidentStatus::Resolved, IncidentStatus::Blocked],
    'resolved to resolved' => [IncidentStatus::Resolved, IncidentStatus::Resolved],
]);

test('repeating a completed transition does not create a duplicate audit entry', function () {
    $incident = Incident::factory()->create();
    $action = new ChangeIncidentStatus;
    $action->handle($incident, IncidentStatus::InReview, 'Demo operator');
    $originalLog = $incident->logs()->sole()->getRawOriginal();

    expect(fn () => $action->handle($incident, IncidentStatus::InReview, 'Another operator'))
        ->toThrow(IncidentStatusConflict::class);

    expect($incident->fresh()->status)->toBe(IncidentStatus::InReview);
    expect($incident->logs()->sole()->getRawOriginal())->toBe($originalLog);
});

test('stale instances use the actual previous state for validation and audit', function () {
    $incident = Incident::factory()->create();
    $action = new ChangeIncidentStatus;
    $action->handle($incident->fresh(), IncidentStatus::InReview, 'First operator');

    $updated = $action->handle($incident, IncidentStatus::Blocked, 'Second operator');

    expect($incident->status)->toBe(IncidentStatus::Open);
    expect($updated->status)->toBe(IncidentStatus::Blocked);
    $this->assertDatabaseHas('incidents', ['id' => $incident->id, 'status' => 'blocked']);
    $this->assertDatabaseCount('incident_logs', 2);
    $this->assertDatabaseHas('incident_logs', [
        'incident_id' => $incident->id,
        'action' => 'status_changed',
        'old_value' => 'in_review',
        'new_value' => 'blocked',
        'user_name' => 'Second operator',
    ]);
});

test('stale instances cannot permit a transition rejected by the current database state', function () {
    $incident = Incident::factory()->create();
    $action = new ChangeIncidentStatus;
    $reviewed = $action->handle($incident->fresh(), IncidentStatus::InReview, 'First operator');
    $action->handle($reviewed, IncidentStatus::Resolved, 'First operator');

    expect(fn () => $action->handle($incident, IncidentStatus::InReview, 'Second operator'))
        ->toThrow(IncidentStatusConflict::class);

    expect($incident->fresh()->status)->toBe(IncidentStatus::Resolved);
    $this->assertDatabaseCount('incident_logs', 2);
});

test('invalid actors leave status and history unchanged', function (string $actorName, string $message) {
    $incident = Incident::factory()->create();

    expect(fn () => (new ChangeIncidentStatus)->handle($incident, IncidentStatus::InReview, $actorName))
        ->toThrow(function (ValidationException $exception) use ($message) {
            expect($exception->errors())->toBe(['actor_name' => [$message]]);
        });

    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
})->with([
    'empty name' => ['', 'El nombre del actor es obligatorio.'],
    'whitespace name' => ['   ', 'El nombre del actor es obligatorio.'],
    'name beyond the schema limit' => [str_repeat('á', 121), 'El nombre del actor no puede superar los 120 caracteres.'],
]);

test('SQL errors writing the audit entry roll back the status and propagate', function () {
    $incident = Incident::factory()->create();
    $original = $incident->fresh()->getRawOriginal();
    $event = 'eloquent.creating: '.IncidentLog::class;
    // Force a real database constraint failure after the status update.
    Event::listen($event, function (IncidentLog $log) {
        $log->action = null;
    });

    try {
        expect(fn () => (new ChangeIncidentStatus)->handle($incident, IncidentStatus::InReview, 'Demo operator'))
            ->toThrow(QueryException::class, '1048');
    } finally {
        Event::forget($event);
    }

    expect($incident->fresh()->getRawOriginal())->toBe($original);
    $this->assertDatabaseCount('incident_logs', 0);
});

test('errors after writing both records roll back the status and log while preserving history', function () {
    $incident = Incident::factory()->create();
    $previousLog = IncidentLog::factory()->for($incident)->create();
    $original = $incident->fresh()->getRawOriginal();
    $event = 'eloquent.created: '.IncidentLog::class;
    $failure = new RuntimeException('Audit persistence failed.');
    Event::listen($event, function (IncidentLog $log) use ($incident, $failure) {
        $this->assertModelExists($log);
        $this->assertDatabaseHas('incidents', ['id' => $incident->id, 'status' => 'in_review']);

        throw $failure;
    });

    try {
        expect(fn () => (new ChangeIncidentStatus)->handle($incident, IncidentStatus::InReview, 'Demo operator'))
            ->toThrow(function (RuntimeException $exception) use ($failure) {
                expect($exception)->toBe($failure);
            });
    } finally {
        Event::forget($event);
    }

    expect($incident->fresh()->getRawOriginal())->toBe($original);
    $this->assertDatabaseCount('incident_logs', 1);
    $this->assertModelExists($previousLog);
});

test('cancelled model writes fail explicitly and roll back the operation', function (string $event, string $message) {
    $incident = Incident::factory()->create();
    Event::listen($event, fn (): bool => false);

    try {
        expect(fn () => (new ChangeIncidentStatus)->handle($incident, IncidentStatus::InReview, 'Demo operator'))
            ->toThrow(RuntimeException::class, $message);
    } finally {
        Event::forget($event);
    }

    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
})->with([
    'incident update cancelled' => ['eloquent.updating: '.Incident::class, 'Unable to persist the incident status.'],
    'log insertion cancelled' => ['eloquent.creating: '.IncidentLog::class, 'Unable to persist the incident status log.'],
]);

test('missing incidents fail without creating audit entries', function () {
    $incident = Incident::factory()->create();
    $incident->delete();

    expect(fn () => (new ChangeIncidentStatus)->handle($incident, IncidentStatus::InReview, 'Demo operator'))
        ->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseCount('incident_logs', 0);
});
