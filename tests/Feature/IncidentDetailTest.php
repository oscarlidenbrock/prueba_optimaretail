<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\IncidentLog;
use App\Models\User;

/** @return array<string, string> */
function incidentDetailHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()) ?? '',
    ];
}

test('detail exposes complete incident data and audit records through the Inertia contract', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create([
        'title' => 'Printer offline', 'description' => 'Check the cable.',
        'status' => 'in_review', 'priority' => 'high',
        'requester_name' => 'Requester', 'assigned_to' => 'Operator',
        'created_at' => '2026-10-01 10:00:00', 'updated_at' => '2026-10-02 11:00:00',
    ]);
    $log = IncidentLog::factory()->for($incident)->create([
        'action' => 'status_changed', 'old_value' => 'open', 'new_value' => 'in_review',
        'user_name' => 'Operator', 'metadata' => ['source' => 'test'], 'created_at' => '2026-10-02 11:00:00',
    ]);
    $comment = IncidentComment::factory()->for($incident)->create([
        'author_name' => 'Operator', 'body' => 'Cable checked.',
        'created_at' => '2026-10-02 12:00:00', 'updated_at' => '2026-10-02 12:00:00',
    ]);
    IncidentLog::factory()->create();
    IncidentComment::factory()->create();

    $this->get(route('incidents.show', $incident), incidentDetailHeaders())
        ->assertOk()->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'incidents/show')
        ->assertJsonPath('props.incident', [
            'id' => $incident->id, 'title' => 'Printer offline', 'description' => 'Check the cable.',
            'status' => 'in_review', 'priority' => 'high', 'requester_name' => 'Requester', 'assigned_to' => 'Operator',
            'created_at' => '2026-10-01T10:00:00.000000Z', 'updated_at' => '2026-10-02T11:00:00.000000Z',
            'logs' => [[
                'id' => $log->id, 'incident_id' => $incident->id, 'action' => 'status_changed',
                'old_value' => 'open', 'new_value' => 'in_review', 'user_name' => 'Operator',
                'metadata' => ['source' => 'test'], 'created_at' => '2026-10-02T11:00:00.000000Z',
            ]],
            'comments' => [[
                'id' => $comment->id, 'incident_id' => $incident->id, 'author_name' => 'Operator', 'body' => 'Cable checked.',
                'created_at' => '2026-10-02T12:00:00.000000Z', 'updated_at' => '2026-10-02T12:00:00.000000Z',
            ]],
        ]);
});

test('detail orders logs newest first and comments oldest first with ID tie breakers', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();
    $newLog = IncidentLog::factory()->for($incident)->create(['created_at' => '2026-10-02 10:00:00']);
    $oldLog = IncidentLog::factory()->for($incident)->create(['created_at' => '2026-10-01 10:00:00']);
    $tiedLog = IncidentLog::factory()->for($incident)->create(['created_at' => '2026-10-02 10:00:00']);
    $newComment = IncidentComment::factory()->for($incident)->create(['created_at' => '2026-10-02 10:00:00']);
    $oldComment = IncidentComment::factory()->for($incident)->create(['created_at' => '2026-10-01 10:00:00']);
    $tiedComment = IncidentComment::factory()->for($incident)->create(['created_at' => '2026-10-01 10:00:00']);

    $response = $this->get(route('incidents.show', $incident), incidentDetailHeaders())->assertOk();

    expect(array_column($response->json('props.incident.logs'), 'id'))->toBe([$tiedLog->id, $newLog->id, $oldLog->id]);
    expect(array_column($response->json('props.incident.comments'), 'id'))->toBe([$oldComment->id, $tiedComment->id, $newComment->id]);
});

test('detail exposes only transitions allowed by the current server status', function (string $status, array $allowed) {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create(['status' => $status]);

    $this->get(route('incidents.show', $incident), incidentDetailHeaders())->assertOk()
        ->assertJsonPath('props.allowedTransitions', $allowed);
})->with([
    'open' => ['open', ['in_review']],
    'review' => ['in_review', ['blocked', 'resolved']],
    'blocked' => ['blocked', []],
    'resolved' => ['resolved', []],
]);

test('status changes return refreshed detail and updated allowed transitions', function () {
    $this->actingAs(User::factory()->create(['name' => 'Operator']));
    $incident = Incident::factory()->create();

    $this->patch(route('incidents.status.update', $incident), ['status' => 'in_review'], incidentDetailHeaders())
        ->assertRedirect(route('incidents.show', $incident));

    $this->get(route('incidents.show', $incident), incidentDetailHeaders())->assertOk()
        ->assertJsonPath('props.incident.status', 'in_review')
        ->assertJsonPath('props.allowedTransitions', ['blocked', 'resolved'])
        ->assertJsonCount(1, 'props.incident.logs')
        ->assertJsonPath('props.incident.logs.0.user_name', 'Operator');
});

test('stale status conflicts return refreshed detail with scoped errors and authoritative actions', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create(['status' => 'resolved']);

    $this->patch(route('incidents.status.update', $incident), ['status' => 'blocked'], [
        ...incidentDetailHeaders(), 'X-Inertia-Error-Bag' => 'statusChange',
    ])->assertRedirect(route('incidents.show', $incident));

    $this->get(route('incidents.show', $incident), [
        ...incidentDetailHeaders(), 'X-Inertia-Error-Bag' => 'statusChange',
    ])->assertOk()->assertJsonPath('props.incident.status', 'resolved')
        ->assertJsonPath('flash.toast.code', 'incident_status_conflict')
        ->assertJsonPath('props.allowedTransitions', [])
        ->assertJsonPath('props.errors', ['statusChange' => ['status' => 'El cambio de estado solicitado no está permitido para el estado actual de la incidencia.']]);

    $this->assertDatabaseCount('incident_logs', 0);
});

test('detail supports empty history and comments and an optional assignee', function () {
    $this->actingAs(User::factory()->unverified()->create());
    $incident = Incident::factory()->create(['assigned_to' => null]);

    $this->get(route('incidents.show', $incident), incidentDetailHeaders())->assertOk()
        ->assertJsonPath('props.incident.assigned_to', null)
        ->assertJsonPath('props.incident.logs', [])->assertJsonPath('props.incident.comments', []);
});

test('guests cannot read detail or write comments', function () {
    $incident = Incident::factory()->create();

    $this->get(route('incidents.show', $incident))->assertRedirect(route('login'));
    $this->post(route('incidents.comments.store', $incident), ['body' => 'Check'])->assertRedirect(route('login'));
    $this->getJson(route('incidents.show', $incident))->assertUnauthorized();
    $this->postJson(route('incidents.comments.store', $incident), ['body' => 'Check'])->assertUnauthorized();

    $this->assertDatabaseCount('incident_comments', 0);
});

test('missing incidents return 404 for detail and comment creation', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('incidents.show', 999999), incidentDetailHeaders())->assertNotFound();
    $this->post(route('incidents.comments.store', 999999), ['body' => 'Check'])->assertNotFound();

    $this->assertDatabaseCount('incident_comments', 0);
});

test('comments use the authenticated author and route incident ignoring forged attributes', function () {
    $this->freezeSecond();
    $this->actingAs(User::factory()->unverified()->create(['name' => 'Real operator']));
    $incident = Incident::factory()->create();
    $other = Incident::factory()->create();
    $before = $incident->fresh()->getAttributes();

    $this->post(route('incidents.comments.store', $incident), [
        'body' => '  Cable checked.  ', 'author_name' => 'Impostor', 'incident_id' => $other->id,
        'user_name' => 'Impostor', 'user_id' => 999, 'status' => 'resolved', 'created_at' => '2000-01-01 00:00:00',
    ], incidentDetailHeaders())->assertStatus(303)->assertRedirect(route('incidents.show', $incident))
        ->assertSessionHasNoErrors()->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Comentario añadido correctamente.');

    $this->assertDatabaseCount('incident_comments', 1);
    $this->assertDatabaseHas('incident_comments', [
        'incident_id' => $incident->id, 'author_name' => 'Real operator', 'body' => 'Cable checked.',
        'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
    ]);
    $this->assertDatabaseCount('incident_logs', 0);
    expect($incident->fresh()->getAttributes())->toBe($before);
});

test('invalid comment bodies return Spanish validation errors without writing', function (array $input, string $message) {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();

    $this->postJson(route('incidents.comments.store', $incident), $input)->assertUnprocessable()
        ->assertJsonValidationErrors(['body'])->assertJsonPath('errors.body.0', $message)->assertInertiaFlashMissing('toast');

    $this->assertDatabaseCount('incident_comments', 0);
    $this->assertDatabaseCount('incident_logs', 0);
})->with([
    'missing' => [[], 'El comentario es obligatorio y no puede estar vacío.'],
    'null' => [['body' => null], 'El comentario es obligatorio y no puede estar vacío.'],
    'empty' => [['body' => ''], 'El comentario es obligatorio y no puede estar vacío.'],
    'whitespace' => [['body' => " \n\t "], 'El comentario es obligatorio y no puede estar vacío.'],
    'array' => [['body' => ['text']], 'El comentario debe ser texto.'],
    'numeric' => [['body' => 123], 'El comentario debe ser texto.'],
    'too long' => [['body' => str_repeat('😀', 10001)], 'El comentario no puede superar los 10000 caracteres.'],
]);

test('Inertia comment validation redirects back with field errors', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();

    $this->from(route('incidents.show', $incident))->post(route('incidents.comments.store', $incident), [], incidentDetailHeaders())
        ->assertStatus(302)->assertRedirect(route('incidents.show', $incident))
        ->assertSessionHasErrors(['body' => 'El comentario es obligatorio y no puede estar vacío.']);

    $this->assertDatabaseCount('incident_comments', 0);
});

test('comments accept the multibyte body and author length boundaries', function () {
    $this->actingAs(User::factory()->create(['name' => str_repeat('ñ', 120)]));
    $incident = Incident::factory()->create();
    $body = str_repeat('😀', 10000);

    $this->post(route('incidents.comments.store', $incident), ['body' => $body])
        ->assertRedirect(route('incidents.show', $incident))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('incident_comments', ['incident_id' => $incident->id, 'body' => $body, 'author_name' => str_repeat('ñ', 120)]);
});

test('client authors cannot replace invalid authenticated names', function (string $name, string $message) {
    $this->actingAs(User::factory()->create(['name' => $name]));
    $incident = Incident::factory()->create();

    $this->postJson(route('incidents.comments.store', $incident), ['body' => 'Check', 'author_name' => 'Replacement'])
        ->assertUnprocessable()->assertJsonPath('errors.author_name.0', $message);

    $this->assertDatabaseCount('incident_comments', 0);
})->with([
    'blank name' => ['   ', 'El usuario autenticado debe tener un nombre válido para comentar.'],
    'long name' => [str_repeat('a', 121), 'El nombre del usuario autenticado no puede superar los 120 caracteres para comentar.'],
]);
