<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Incident;
use App\Models\User;

/** @return array<string, string> */
function validIncidentPayload(): array
{
    return [
        'title' => 'Printer offline',
        'description' => 'The receipt printer does not respond.',
        'priority' => 'high',
        'requester_name' => 'Demo requester',
    ];
}

/** @return array<string, string> */
function incidentListingHeaders(): array
{
    // Follow Inertia's asset version protocol without disabling its middleware.
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()) ?? '',
    ];
}

test('guests cannot list or create incidents', function () {
    $this->get(route('incidents.index'))->assertRedirect(route('login'));
    $this->post(route('incidents.store'), validIncidentPayload())->assertRedirect(route('login'));

    $this->assertDatabaseCount('incidents', 0);
});

test('authenticated users create open incidents with the selected priority and optional responsible', function (
    string $priority,
    array $optionalInput,
    ?string $responsible,
) {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('incidents.store'), [
        ...validIncidentPayload(), 'priority' => $priority, ...$optionalInput,
    ]);

    $response->assertRedirect(route('incidents.index'))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('incidents', 1);
    $this->assertDatabaseHas('incidents', [
        ...validIncidentPayload(), 'priority' => $priority, 'assigned_to' => $responsible, 'status' => 'open',
    ]);
    $this->assertDatabaseCount('incident_logs', 0);
})->with([
    'low and omitted responsible' => ['low', [], null],
    'medium and null responsible' => ['medium', ['assigned_to' => null], null],
    'high and named responsible' => ['high', ['assigned_to' => 'Demo operator'], 'Demo operator'],
    'empty responsible' => ['medium', ['assigned_to' => '   '], null],
]);

test('creation reports all missing required fields in Spanish without persisting', function () {
    $this->actingAs(User::factory()->create());

    $this->from(route('dashboard'))->post(route('incidents.store'), [])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors([
            'title' => 'El título es obligatorio.',
            'description' => 'La descripción es obligatoria.',
            'priority' => 'La prioridad es obligatoria.',
            'requester_name' => 'El solicitante es obligatorio.',
        ]);

    $this->assertDatabaseCount('incidents', 0);
});

test('creation rejects invalid input with Spanish validation feedback', function (string $field, mixed $value, string $message) {
    $this->actingAs(User::factory()->create());

    $this->post(route('incidents.store'), [...validIncidentPayload(), $field => $value])
        ->assertSessionHasErrors([$field => $message]);

    $this->assertDatabaseCount('incidents', 0);
})->with([
    'blank title' => ['title', '   ', 'El título es obligatorio.'],
    'title type' => ['title', ['unexpected'], 'El título debe ser texto.'],
    'title length' => ['title', str_repeat('á', 256), 'El título no puede superar los 255 caracteres.'],
    'description type' => ['description', ['unexpected'], 'La descripción debe ser texto.'],
    'description length' => ['description', str_repeat('a', 10001), 'La descripción no puede superar los 10000 caracteres.'],
    'priority type' => ['priority', ['high'], 'La prioridad debe ser texto.'],
    'priority enum' => ['priority', 'urgent', 'La prioridad seleccionada no es válida.'],
    'requester type' => ['requester_name', ['unexpected'], 'El solicitante debe ser texto.'],
    'requester length' => ['requester_name', str_repeat('a', 121), 'El solicitante no puede superar los 120 caracteres.'],
    'responsible type' => ['assigned_to', ['unexpected'], 'El responsable debe ser texto.'],
    'responsible length' => ['assigned_to', str_repeat('a', 121), 'El responsable no puede superar los 120 caracteres.'],
]);

test('clients cannot supply an initial status even when it is null or already open', function (mixed $status) {
    $this->actingAs(User::factory()->create());

    $this->post(route('incidents.store'), [...validIncidentPayload(), 'status' => $status])
        ->assertSessionHasErrors(['status' => 'El estado inicial lo establece el servidor y no debe enviarse.']);

    $this->assertDatabaseCount('incidents', 0);
})->with(['open', 'in_review', 'blocked', 'resolved', 'unknown', null, '', [['open']]]);

test('creation accepts schema boundaries while ignoring unvalidated database attributes', function () {
    $this->freezeSecond();
    $this->actingAs(User::factory()->create());
    $input = [
        'title' => str_repeat('á', 255),
        'description' => str_repeat('🔥', 10000),
        'priority' => 'low',
        'requester_name' => str_repeat('a', 120),
        'assigned_to' => str_repeat('b', 120),
    ];

    $this->post(route('incidents.store'), [
        ...$input, 'id' => 999999, 'created_at' => '2000-01-01 00:00:00', 'updated_at' => '2000-01-01 00:00:00',
    ])->assertRedirect(route('incidents.index'))->assertSessionHasNoErrors();

    $incident = Incident::query()->sole();
    expect($incident->id)->not->toBe(999999);
    expect($incident->created_at)->toEqual(now());
    expect($incident->updated_at)->toEqual(now());
    $this->assertDatabaseHas('incidents', [...$input, 'id' => $incident->id, 'status' => 'open']);
});

test('authenticated users receive the empty Inertia listing contract', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('incidents.index'), incidentListingHeaders())
        ->assertOk()->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'incidents/index')
        ->assertJsonPath('props.incidents.data', [])
        ->assertJsonPath('props.incidents.total', 0)
        ->assertJsonPath('props.filters', ['status' => null, 'priority' => null, 'q' => '']);
});

test('combined filters group title and description matches inside status and priority constraints', function () {
    $this->freezeSecond();
    $this->actingAs(User::factory()->create());
    $titleMatch = Incident::factory()->create(['title' => 'Printer unavailable', 'description' => 'Receipt failure', 'priority' => 'high']);
    $descriptionMatch = Incident::factory()->create(['title' => 'Checkout unavailable', 'description' => 'The printer does not respond', 'priority' => 'high']);
    Incident::factory()->create(['title' => 'Unrelated', 'description' => 'Printer failure', 'status' => 'in_review', 'priority' => 'high']);
    Incident::factory()->create(['title' => 'Printer failure', 'priority' => 'low']);
    Incident::factory()->create(['title' => 'Other issue', 'description' => 'Other details', 'priority' => 'high']);
    $filters = ['status' => 'open', 'priority' => 'high', 'q' => 'printer'];

    $response = $this->get(route('incidents.index', $filters), incidentListingHeaders());

    $response->assertOk()->assertJsonPath('props.filters', $filters)->assertJsonPath('props.incidents.total', 2);
    expect(array_column($response->json('props.incidents.data'), 'id'))->toBe([$descriptionMatch->id, $titleMatch->id]);
    $response->assertJsonMissingPath('props.incidents.data.0.logs')->assertJsonMissingPath('props.incidents.data.0.comments');
});

test('state and priority filters can each be used independently', function (string $field, string $value) {
    $this->actingAs(User::factory()->create());
    $match = Incident::factory()->create([$field => $value]);
    Incident::factory()->create();

    $response = $this->get(route('incidents.index', [$field => $value]), incidentListingHeaders());

    $response->assertOk()->assertJsonPath('props.incidents.total', 1)->assertJsonPath('props.incidents.data.0.id', $match->id);
})->with([
    'status only' => ['status', 'resolved'],
    'priority only' => ['priority', 'low'],
]);

test('free text is bound safely and SQL wildcard characters are treated literally', function (string $search) {
    $this->actingAs(User::factory()->create());
    $match = Incident::factory()->create(['title' => 'Issue '.$search, 'description' => 'Match']);
    Incident::factory()->create(['title' => 'Other issue', 'description' => 'Other details']);

    $this->get(route('incidents.index', ['q' => $search]), incidentListingHeaders())
        ->assertOk()->assertJsonPath('props.incidents.total', 1)->assertJsonPath('props.incidents.data.0.id', $match->id);
})->with(['%', '_', '!', "' OR 1=1 --"]);

test('pagination orders by creation date and id and retains only validated filters', function () {
    $this->freezeSecond();
    $this->actingAs(User::factory()->create());
    $incidents = Incident::factory()->count(16)->create(['title' => 'Printer issue', 'status' => 'in_review', 'priority' => 'high']);
    $older = Incident::factory()->create(['title' => 'Printer issue', 'status' => 'in_review', 'priority' => 'high', 'created_at' => now()->subDay()]);
    $filters = ['status' => 'in_review', 'priority' => 'high', 'q' => 'Printer'];

    $first = $this->get(route('incidents.index', [...$filters, 'ignored' => 'untrusted']), incidentListingHeaders());
    $second = $this->get(route('incidents.index', [...$filters, 'page' => 2]), incidentListingHeaders());

    $first->assertOk()->assertJsonCount(15, 'props.incidents.data')->assertJsonPath('props.incidents.total', 17);
    expect(array_column($first->json('props.incidents.data'), 'id'))->toBe($incidents->reverse()->take(15)->pluck('id')->values()->all());
    $second->assertOk()->assertJsonPath('props.incidents.current_page', 2);
    expect(array_column($second->json('props.incidents.data'), 'id'))->toBe([$incidents->first()->id, $older->id]);
    parse_str(parse_url($first->json('props.incidents.next_page_url'), PHP_URL_QUERY), $nextQuery);
    expect($nextQuery)->toEqual([...$filters, 'page' => '2']);
    parse_str(parse_url($second->json('props.incidents.prev_page_url'), PHP_URL_QUERY), $previousQuery);
    expect($previousQuery)->toEqual([...$filters, 'page' => '1']);
});

test('blank filters do not restrict the listing', function () {
    $this->actingAs(User::factory()->create());
    Incident::factory()->create();

    $this->get(route('incidents.index', ['status' => '', 'priority' => '', 'q' => '  ']), incidentListingHeaders())
        ->assertOk()->assertJsonPath('props.incidents.total', 1)
        ->assertJsonPath('props.filters', ['status' => null, 'priority' => null, 'q' => '']);
});

test('invalid filters and pagination values receive Spanish errors', function (string $field, mixed $value, string $message) {
    $this->actingAs(User::factory()->create());

    $this->from(route('dashboard'))->get(route('incidents.index', [$field => $value]))
        ->assertRedirect(route('dashboard'))->assertSessionHasErrors([$field => $message]);
})->with([
    'status type' => ['status', ['open'], 'El filtro de estado debe ser texto.'],
    'status enum' => ['status', 'closed', 'El estado seleccionado no es válido.'],
    'priority type' => ['priority', ['high'], 'El filtro de prioridad debe ser texto.'],
    'priority enum' => ['priority', 'urgent', 'La prioridad seleccionada no es válida.'],
    'search type' => ['q', ['unexpected'], 'La búsqueda debe ser texto.'],
    'search length' => ['q', str_repeat('a', 256), 'La búsqueda no puede superar los 255 caracteres.'],
    'page type' => ['page', 'other', 'La página debe ser un número entero.'],
    'fractional page' => ['page', '1.5', 'La página debe ser un número entero.'],
    'zero page' => ['page', '0', 'La página debe ser mayor o igual que 1.'],
    'negative page' => ['page', '-1', 'La página debe ser mayor o igual que 1.'],
    'oversized page' => ['page', '9223372036854775807', 'La página no puede superar 2147483647.'],
]);
