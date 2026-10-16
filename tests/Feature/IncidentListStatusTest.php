<?php

use App\Enums\IncidentStatus;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Incident;
use App\Models\User;

/** @return array<string, string> */
function incidentListStatusHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()) ?? '',
    ];
}

test('listing exposes the server destinations for each state without loading history', function (string $status, array $expected) {
    $this->actingAs(User::factory()->create());
    Incident::factory()->create(['status' => $status]);

    $this->get(route('incidents.index'), incidentListStatusHeaders())->assertOk()
        ->assertJsonPath('props.incidents.data.0.allowed_transitions', $expected)
        ->assertJsonMissingPath('props.incidents.data.0.logs');
})->with([
    'open' => ['open', ['in_review']],
    'in review' => ['in_review', ['blocked', 'resolved']],
    'blocked' => ['blocked', []],
    'resolved' => ['resolved', []],
]);

test('list status changes preserve search priority and page and refresh destinations with audit', function (string $old, string $new, array $allowed) {
    $this->freezeSecond();
    $this->actingAs(User::factory()->create(['name' => 'List operator']));
    $incident = Incident::factory()->create(['status' => $old, 'title' => 'Printer offline', 'priority' => 'high']);
    Incident::factory()->count(15)->create(['title' => 'Printer problem', 'priority' => 'high']);
    $context = ['status' => null, 'priority' => 'high', 'q' => 'Printer', 'page' => 2];
    $url = route('incidents.index', $context);

    $this->from($url)->patch(route('incidents.status.update', $incident), [
        'status' => $new, 'list_context' => $context, 'actor_name' => 'Forged actor',
    ], incidentListStatusHeaders())->assertStatus(303)->assertRedirect($url)->assertSessionHasNoErrors();

    $this->get($url, incidentListStatusHeaders())->assertOk()
        ->assertJsonPath('component', 'incidents/index')
        ->assertJsonPath('props.filters', ['status' => null, 'priority' => 'high', 'q' => 'Printer'])
        ->assertJsonPath('props.incidents.current_page', 2)
        ->assertJsonPath('props.incidents.data.0.id', $incident->id)
        ->assertJsonPath('props.incidents.data.0.status', $new)
        ->assertJsonPath('props.incidents.data.0.allowed_transitions', $allowed)
        ->assertJsonPath('flash.toast.type', 'success');
    $this->assertDatabaseHas('incident_logs', [
        'incident_id' => $incident->id, 'action' => 'status_changed',
        'old_value' => $old, 'new_value' => $new, 'user_name' => 'List operator',
    ]);
    $this->assertDatabaseCount('incident_logs', 1);
})->with([
    'review' => ['open', 'in_review', ['blocked', 'resolved']],
    'block' => ['in_review', 'blocked', []],
    'resolve' => ['in_review', 'resolved', []],
]);

test('changing a filtered state keeps the filter and removes the row from refreshed results', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();
    $context = ['status' => 'open', 'priority' => null, 'q' => '', 'page' => 1];
    $url = route('incidents.index', ['status' => 'open', 'page' => 1]);

    $this->from($url)->patch(route('incidents.status.update', $incident), [
        'status' => 'in_review', 'list_context' => $context,
    ], incidentListStatusHeaders())->assertRedirect($url);

    $this->get($url, incidentListStatusHeaders())->assertOk()
        ->assertJsonPath('props.filters.status', 'open')->assertJsonPath('props.incidents.data', [])
        ->assertJsonPath('flash.toast.type', 'success');
    expect($incident->fresh()->status)->toBe(IncidentStatus::InReview);
});

test('stale list conflicts refresh the row and return a scoped error without extra logs', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create(['status' => 'resolved']);
    $context = ['priority' => 'medium', 'q' => '', 'page' => 1];
    $url = route('incidents.index', ['priority' => 'medium', 'page' => 1]);
    $headers = [...incidentListStatusHeaders(), 'X-Inertia-Error-Bag' => 'incidentStatus'.$incident->id];

    $this->from($url)->patch(route('incidents.status.update', $incident), [
        'status' => 'blocked', 'list_context' => $context,
    ], $headers)->assertStatus(303)->assertRedirect($url);

    $this->get($url, $headers)->assertOk()
        ->assertJsonPath('props.incidents.data.0.status', 'resolved')
        ->assertJsonPath('props.incidents.data.0.allowed_transitions', [])
        ->assertJsonPath('props.errors.incidentStatus'.$incident->id.'.status', 'El cambio de estado solicitado no está permitido para el estado actual de la incidencia.')
        ->assertJsonPath('flash.toast.code', 'incident_status_conflict');
    $this->assertDatabaseCount('incident_logs', 0);
});

test('list JSON conflicts retain the existing 409 contract', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create(['status' => 'resolved']);

    $this->patchJson(route('incidents.status.update', $incident), [
        'status' => 'blocked', 'list_context' => ['page' => 1],
    ])->assertConflict()->assertJsonPath('code', 'incident_status_conflict');
    $this->assertDatabaseCount('incident_logs', 0);
});

test('list validation errors return to the exact listing context without writing', function () {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();
    $context = ['status' => 'open', 'priority' => 'medium', 'q' => 'Printer', 'page' => 2];
    $url = route('incidents.index', $context);

    $this->from($url)->patch(route('incidents.status.update', $incident), [
        'status' => 'closed', 'list_context' => $context,
    ], incidentListStatusHeaders())->assertStatus(303)->assertRedirect($url)
        ->assertSessionHasErrors(['status' => 'El estado solicitado no es válido.']);
    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
});

test('untrusted listing contexts fail validation before changing state', function (mixed $context, string $field) {
    $this->actingAs(User::factory()->create());
    $incident = Incident::factory()->create();

    $this->patchJson(route('incidents.status.update', $incident), [
        'status' => 'in_review', 'list_context' => $context,
    ])->assertUnprocessable()->assertJsonValidationErrors([$field]);
    expect($incident->fresh()->status)->toBe(IncidentStatus::Open);
    $this->assertDatabaseCount('incident_logs', 0);
})->with([
    'external redirect' => [['redirect' => 'https://example.com'], 'list_context'],
    'not an array' => ['https://example.com', 'list_context'],
    'null' => [null, 'list_context'],
    'unknown state' => [['status' => 'closed'], 'list_context.status'],
    'unknown priority' => [['priority' => 'urgent'], 'list_context.priority'],
    'invalid page' => [['page' => 0], 'list_context.page'],
    'long search' => [['q' => str_repeat('a', 256)], 'list_context.q'],
]);
