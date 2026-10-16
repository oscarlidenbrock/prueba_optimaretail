<?php

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('guests cannot change incident status through browser or JSON requests', function () {
    $incident = Incident::factory()->create();
    $url = route('incidents.status.update', ['incident' => $incident]);

    $this->patch($url, ['status' => 'in_review'])->assertRedirect(route('login'));
    $this->patchJson($url, ['status' => 'in_review'])->assertUnauthorized();

    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
});

test('valid HTTP transitions record the authenticated actor and return an Inertia success flash', function (
    string $oldStatus,
    string $newStatus,
) {
    $this->freezeSecond();
    $user = User::factory()->create(['name' => 'Authenticated operator']);
    $this->actingAs($user);
    $incident = Incident::factory()->create(['status' => $oldStatus]);

    $response = $this->patch(route('incidents.status.update', ['incident' => $incident]), [
        'status' => $newStatus,
        'actor_name' => 'Impersonated actor',
        'user_name' => 'Impersonated actor',
        'user_id' => 999999,
    ], ['X-Inertia' => 'true']);

    $response->assertStatus(303)->assertRedirect(route('incidents.index'))->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Estado de la incidencia actualizado correctamente.');
    $this->assertDatabaseHas('incidents', ['id' => $incident->id, 'status' => $newStatus]);
    $this->assertDatabaseCount('incident_logs', 1);
    $this->assertDatabaseHas('incident_logs', [
        'incident_id' => $incident->id,
        'action' => 'status_changed',
        'old_value' => $oldStatus,
        'new_value' => $newStatus,
        'user_name' => 'Authenticated operator',
        'created_at' => now()->toDateTimeString(),
    ]);
})->with([
    'open to in review' => ['open', 'in_review'],
    'in review to blocked' => ['in_review', 'blocked'],
    'in review to resolved' => ['in_review', 'resolved'],
]);

test('malformed status input returns JSON validation errors rather than a state conflict', function (array $payload, string $message) {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();
    $original = $incident->fresh()->getRawOriginal();

    $this->patchJson(route('incidents.status.update', ['incident' => $incident]), $payload)
        ->assertUnprocessable()->assertJsonPath('errors.status.0', $message)->assertJsonMissingPath('code');

    expect($incident->fresh()->getRawOriginal())->toBe($original);
    $this->assertDatabaseCount('incident_logs', 0);
})->with([
    'missing' => [[], 'El nuevo estado es obligatorio.'],
    'null' => [['status' => null], 'El nuevo estado es obligatorio.'],
    'blank' => [['status' => '  '], 'El nuevo estado es obligatorio.'],
    'array' => [['status' => ['in_review']], 'El nuevo estado debe ser texto.'],
    'number' => [['status' => 1], 'El nuevo estado debe ser texto.'],
    'unknown enum value' => [['status' => 'closed'], 'El estado solicitado no es válido.'],
]);

test('Inertia validation errors redirect with Spanish field errors and no conflict flash', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();

    $this->from(route('incidents.index'))
        ->patch(route('incidents.status.update', ['incident' => $incident]), ['status' => 'closed'], ['X-Inertia' => 'true'])
        ->assertStatus(303)->assertRedirect(route('incidents.index'))
        ->assertSessionHasErrors(['status' => 'El estado solicitado no es válido.'])
        ->assertInertiaFlashMissing('toast.code');

    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
});

test('valid enum values incompatible with the current status return JSON conflict errors', function (
    string $currentStatus,
    string $requestedStatus,
) {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create(['status' => $currentStatus]);
    $message = 'El cambio de estado solicitado no está permitido para el estado actual de la incidencia.';

    $this->patchJson(route('incidents.status.update', ['incident' => $incident]), ['status' => $requestedStatus])
        ->assertConflict()->assertJsonPath('code', 'incident_status_conflict')
        ->assertJsonPath('message', $message)->assertJsonPath('errors.status.0', $message);

    expect($incident->fresh()->status->value)->toBe($currentStatus);
    $this->assertDatabaseCount('incident_logs', 0);
})->with([
    'skipped review' => ['open', 'resolved'],
    'blocked terminal state' => ['blocked', 'in_review'],
    'reopening forbidden' => ['resolved', 'open'],
    'same state' => ['open', 'open'],
]);

test('Inertia state conflicts redirect with field errors and an identifiable error flash', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();
    $message = 'El cambio de estado solicitado no está permitido para el estado actual de la incidencia.';

    $this->patch(route('incidents.status.update', ['incident' => $incident]), ['status' => 'resolved'], ['X-Inertia' => 'true'])
        ->assertStatus(303)->assertRedirect(route('incidents.index'))
        ->assertSessionHasErrors(['status' => $message])
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.code', 'incident_status_conflict')
        ->assertInertiaFlash('toast.message', $message);

    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
});

test('repeated HTTP transitions produce a conflict without duplicating audit history', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();
    $url = route('incidents.status.update', ['incident' => $incident]);
    $this->patchJson($url, ['status' => 'in_review'])->assertStatus(303);
    $originalLog = $incident->logs()->sole()->getRawOriginal();

    $this->patchJson($url, ['status' => 'in_review'])->assertConflict();

    expect($incident->fresh()->status)->toBe(IncidentStatus::InReview);
    expect($incident->logs()->sole()->getRawOriginal())->toBe($originalLog);
});

test('existing access rules allow authenticated operators without adding roles or email verification', function () {
    $user = User::factory()->unverified()->create(['name' => 'Ordinary operator']);
    $this->actingAs($user);
    $incident = Incident::factory()->create(['requester_name' => 'Different requester']);

    $this->patch(route('incidents.status.update', ['incident' => $incident]), ['status' => 'in_review'])
        ->assertStatus(303)->assertSessionHasNoErrors();

    expect($incident->fresh()->status)->toBe(IncidentStatus::InReview);
    $this->assertDatabaseHas('incident_logs', ['incident_id' => $incident->id, 'user_name' => 'Ordinary operator']);
});

test('an invalid authenticated actor cannot be replaced with a client supplied actor', function () {
    $this->actingAs(User::factory()->create(['name' => str_repeat('a', 121)]));
    $incident = Incident::factory()->create();

    $this->patchJson(route('incidents.status.update', ['incident' => $incident]), [
        'status' => 'in_review', 'actor_name' => 'Short replacement', 'user_name' => 'Short replacement',
    ])->assertUnprocessable()->assertJsonPath('errors.actor_name.0', 'El nombre del actor no puede superar los 120 caracteres.');

    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
});

test('missing incidents return not found without writing history', function () {
    $this->actingAs(User::factory()->create());

    $this->patchJson(route('incidents.status.update', ['incident' => 999999]), ['status' => 'in_review'])->assertNotFound();

    $this->assertDatabaseCount('incident_logs', 0);
});

test('the status endpoint rejects methods other than PATCH', function (string $method) {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();

    $this->call($method, route('incidents.status.update', ['incident' => $incident]), ['status' => 'in_review'])
        ->assertMethodNotAllowed();

    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
})->with(['GET', 'POST']);

test('audit persistence errors return a server error and roll back without reporting a status conflict', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();
    $original = $incident->fresh()->getRawOriginal();
    $event = 'eloquent.creating: '.IncidentLog::class;
    Event::listen($event, function (IncidentLog $log) {
        $log->action = null;
    });

    try {
        $this->patchJson(route('incidents.status.update', ['incident' => $incident]), ['status' => 'in_review'])
            ->assertServerError()->assertJsonMissingPath('code')->assertInertiaFlashMissing('toast');
    } finally {
        Event::forget($event);
    }

    expect($incident->fresh()->getRawOriginal())->toBe($original);
    $this->assertDatabaseCount('incident_logs', 0);
});
