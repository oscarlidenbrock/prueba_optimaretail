<?php

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\IncidentLog;
use Database\Seeders\InconsistentIncidentSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/** @return array<string, array<int, array<string, mixed>>> */
function diagnosisDataSnapshot(): array
{
    return collect(['incidents', 'incident_logs', 'incident_comments', 'users'])
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all()])
        ->all();
}

test('explicit fixtures create three marked cases without touching existing incidents or users', function () {
    $this->freezeSecond();
    $existing = Incident::factory()->create(['title' => '[SIMULACIÓN] Resuelta sin log de resolución']);
    $log = IncidentLog::factory()->for($existing)->create();
    $comment = IncidentComment::factory()->for($existing)->create();
    $before = diagnosisDataSnapshot();

    $this->seed(InconsistentIncidentSeeder::class);

    $this->assertDatabaseCount('incidents', 4);
    $this->assertDatabaseCount('incident_logs', 4);
    $this->assertDatabaseCount('users', 0);
    expect(DB::table('incidents')->where('id', $existing->id)->first())->toEqual((object) $before['incidents'][0]);
    $this->assertModelExists($log);
    $this->assertModelExists($comment);
    $fixtures = Incident::query()->where('requester_name', 'Simulación de diagnóstico')->orderBy('id')->get();
    expect($fixtures->pluck('status')->map(fn ($status) => $status->value)->all())->toBe(['resolved', 'blocked', 'in_review']);
    expect($fixtures->map(fn (Incident $incident): int => $incident->logs()->count())->all())->toBe([0, 1, 2]);
    foreach ($fixtures as $fixture) {
        expect($fixture->title)->toStartWith('[SIMULACIÓN]');
        expect($fixture->description)->toStartWith('[SIMULACIÓN diagnóstico:v1:');
    }
    expect(IncidentLog::query()->where('user_name', 'Simulación de diagnóstico')->get()->pluck('metadata')->all())->each->toHaveKey('simulation', true);
});

test('repeating explicit fixtures preserves every existing row and timestamp', function () {
    $this->freezeSecond();
    $this->seed(InconsistentIncidentSeeder::class);
    $before = diagnosisDataSnapshot();
    $this->travel(1)->day();

    $this->seed(InconsistentIncidentSeeder::class);

    expect(diagnosisDataSnapshot())->toBe($before);
});

test('fixtures do not overwrite later changes to a previously simulated incident', function () {
    $this->seed(InconsistentIncidentSeeder::class);
    $fixture = Incident::query()->where('status', 'resolved')->firstOrFail();
    $fixture->update(['title' => 'Reviewed simulation', 'status' => 'open']);
    IncidentComment::factory()->for($fixture)->create();
    $before = diagnosisDataSnapshot();

    $this->seed(InconsistentIncidentSeeder::class);

    expect(diagnosisDataSnapshot())->toBe($before);
});

test('diagnosis identifies all three explicit cases and their evidence', function () {
    $this->freezeSecond();
    $this->seed(InconsistentIncidentSeeder::class);
    $fixtures = Incident::query()->orderBy('id')->get();
    $latestMismatchLog = $fixtures[1]->logs()->firstOrFail();
    $duplicateLogs = $fixtures[2]->logs()->get();

    $this->artisan('incidents:diagnose')
        ->expectsOutputToContain("Incidencia #{$fixtures[0]->id} [resolved_without_resolution_log]")
        ->expectsOutputToContain("Incidencia #{$fixtures[1]->id} [status_mismatch] Estado actual=blocked; último log #{$latestMismatchLog->id}")
        ->expectsOutputToContain("Incidencia #{$fixtures[2]->id} [incoherent_status_log] Par de estados duplicado del log #{$duplicateLogs[0]->id}; log #{$duplicateLogs[1]->id}")
        ->expectsOutput('Reparaciones automáticas justificadas: 0. Correcciones aplicadas: 0.')
        ->expectsOutput('Se modificaría: nada. Las propuestas requieren una decisión humana documentada.')
        ->expectsOutputToContain('Incidencias revisadas: 3. Hallazgos: 3. Ningún dato modificado.')
        ->assertExitCode(1);
});

test('default and explicit dry run diagnostics are repeatable and execute only selects', function () {
    $this->seed(InconsistentIncidentSeeder::class);
    $before = diagnosisDataSnapshot();
    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        expect(Artisan::call('incidents:diagnose'))->toBe(1);
        $firstOutput = Artisan::output();
        expect(Artisan::call('incidents:diagnose', ['--dry-run' => true]))->toBe(1);
        expect(Artisan::output())->toBe($firstOutput);
        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($queries)->not->toBeEmpty();
    foreach ($queries as $query) {
        expect(strtolower(ltrim($query['query'])))->toStartWith('select ');
    }
    expect(diagnosisDataSnapshot())->toBe($before);
});

test('empty databases have no findings', function () {
    $this->artisan('incidents:diagnose', ['--dry-run' => true])
        ->expectsOutput('No se detectaron inconsistencias.')
        ->expectsOutput('Incidencias revisadas: 0. Hallazgos: 0. Ningún dato modificado.')
        ->assertExitCode(0);
});

test('apply preserves ambiguous fixtures and reports manual proposals instead of artificial repairs', function () {
    $this->seed(InconsistentIncidentSeeder::class);
    $before = diagnosisDataSnapshot();

    $this->artisan('incidents:diagnose', ['--apply' => true])
        ->expectsOutputToContain('Confirmar la resolución y su evidencia')
        ->expectsOutputToContain('Contrastar estado e historial con evidencia externa')
        ->expectsOutputToContain('Determinar si hubo duplicación o un cambio real mal registrado')
        ->expectsOutput('Reparaciones automáticas justificadas: 0. Correcciones aplicadas: 0.')
        ->expectsOutput('Hallazgos pendientes de revisión manual: 3.')
        ->expectsOutput('No se aplicaron cambios: ningún caso dispone de evidencia suficiente para una reparación automática.')
        ->assertExitCode(1);

    expect(diagnosisDataSnapshot())->toBe($before);
});

test('repeated apply is idempotent and executes only selects for unproven repairs', function () {
    $this->seed(InconsistentIncidentSeeder::class);
    $before = diagnosisDataSnapshot();
    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        expect(Artisan::call('incidents:diagnose', ['--apply' => true]))->toBe(1);
        $firstOutput = Artisan::output();
        expect(Artisan::call('incidents:diagnose', ['--apply' => true]))->toBe(1);
        expect(Artisan::output())->toBe($firstOutput);
        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($queries)->not->toBeEmpty();
    foreach ($queries as $query) {
        expect(strtolower(ltrim($query['query'])))->toStartWith('select ');
    }
    expect(diagnosisDataSnapshot())->toBe($before);
});

test('conflicting execution flags fail before any diagnosis query or write', function () {
    $this->seed(InconsistentIncidentSeeder::class);
    $before = diagnosisDataSnapshot();
    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        $this->artisan('incidents:diagnose', ['--apply' => true, '--dry-run' => true])
            ->expectsOutputToContain('Las opciones --apply y --dry-run no pueden combinarse.')
            ->assertExitCode(2);
        expect(DB::getQueryLog())->toBeEmpty();
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect(diagnosisDataSnapshot())->toBe($before);
});

test('apply leaves correct data untouched and reports no repairs or manual findings', function () {
    Incident::factory()->create();
    $before = diagnosisDataSnapshot();

    $this->artisan('incidents:diagnose', ['--apply' => true])
        ->expectsOutput('No se detectaron inconsistencias.')
        ->expectsOutput('Reparaciones automáticas justificadas: 0. Correcciones aplicadas: 0.')
        ->expectsOutput('Hallazgos pendientes de revisión manual: 0.')
        ->assertExitCode(0);

    expect(diagnosisDataSnapshot())->toBe($before);
});

test('apply does not treat a plausible non-simulated log as authority over the current state', function () {
    $incident = Incident::factory()->create(['status' => 'blocked']);
    IncidentLog::factory()->for($incident)->create([
        'action' => 'status_changed', 'old_value' => 'open', 'new_value' => 'in_review',
        'user_name' => 'Operator', 'metadata' => null,
    ]);
    $before = diagnosisDataSnapshot();

    $this->artisan('incidents:diagnose', ['--apply' => true])
        ->expectsOutputToContain("Incidencia #{$incident->id} [status_mismatch]")
        ->expectsOutput('Reparaciones automáticas justificadas: 0. Correcciones aplicadas: 0.')
        ->expectsOutput('Hallazgos pendientes de revisión manual: 1.')
        ->assertExitCode(1);

    expect(diagnosisDataSnapshot())->toBe($before);
});

test('valid data uses chronological log order rather than insertion order and ignores unrelated actions', function () {
    $incident = Incident::factory()->create(['status' => 'resolved']);
    IncidentLog::factory()->for($incident)->create([
        'action' => 'status_changed', 'old_value' => 'in_review', 'new_value' => 'resolved', 'created_at' => '2026-10-02 10:00:00',
    ]);
    IncidentLog::factory()->for($incident)->create([
        'action' => 'status_changed', 'old_value' => 'open', 'new_value' => 'in_review', 'created_at' => '2026-10-01 10:00:00',
    ]);
    IncidentLog::factory()->for($incident)->create([
        'action' => 'comment_added', 'new_value' => 'blocked', 'created_at' => '2026-10-03 10:00:00',
    ]);
    IncidentComment::factory()->for($incident)->create();
    Incident::factory()->create();

    $this->artisan('incidents:diagnose')->expectsOutput('No se detectaron inconsistencias.')
        ->expectsOutput('Incidencias revisadas: 2. Hallazgos: 0. Ningún dato modificado.')->assertExitCode(0);
});

test('latest status evidence uses descending ID when timestamps tie', function () {
    $this->freezeSecond();
    $incident = Incident::factory()->create(['status' => 'in_review']);
    IncidentLog::factory()->for($incident)->create(['action' => 'status_changed', 'old_value' => 'open', 'new_value' => 'in_review']);
    $latest = IncidentLog::factory()->for($incident)->create(['action' => 'status_changed', 'old_value' => 'in_review', 'new_value' => 'blocked']);

    $this->artisan('incidents:diagnose')
        ->expectsOutputToContain("[status_mismatch] Estado actual=in_review; último log #{$latest->id}: in_review -> blocked")
        ->expectsOutputToContain('Hallazgos: 1.')->assertExitCode(1);
});

test('unrelated resolution actions cannot satisfy a missing status resolution log', function () {
    $incident = Incident::factory()->create(['status' => 'resolved']);
    IncidentLog::factory()->for($incident)->create(['action' => 'note', 'new_value' => 'resolved']);

    $this->artisan('incidents:diagnose')->expectsOutputToContain('[resolved_without_resolution_log]')
        ->expectsOutputToContain('Hallazgos: 1.')->assertExitCode(1);
});

test('invalid state log values and forbidden transitions are reported without altering them', function (?string $old, ?string $new) {
    $incident = Incident::factory()->create();
    if ($new !== null) {
        // Simulate persisted corruption without asking the enum cast to accept an unknown value.
        DB::table('incidents')->where('id', $incident->id)->update(['status' => $new]);
    }
    $log = IncidentLog::factory()->for($incident)->create(['action' => 'status_changed', 'old_value' => $old, 'new_value' => $new]);
    $before = diagnosisDataSnapshot();

    $this->artisan('incidents:diagnose')->expectsOutputToContain("[incoherent_status_log] Valores nulos/desconocidos o transición no permitida; log #{$log->id}")
        ->assertExitCode(1);

    expect(diagnosisDataSnapshot())->toBe($before);
})->with([
    'null previous' => [null, 'in_review'],
    'null next' => ['open', null],
    'unknown previous' => ['unknown', 'in_review'],
    'unknown next' => ['open', 'unknown'],
    'same state' => ['open', 'open'],
    'forbidden reverse' => ['blocked', 'in_review'],
]);

test('individually allowed logs with a broken chronological chain are flagged', function () {
    $this->freezeSecond();
    $incident = Incident::factory()->create(['status' => 'resolved']);
    $previous = IncidentLog::factory()->for($incident)->create(['action' => 'status_changed', 'old_value' => 'in_review', 'new_value' => 'blocked']);
    $latest = IncidentLog::factory()->for($incident)->create(['action' => 'status_changed', 'old_value' => 'in_review', 'new_value' => 'resolved']);

    $this->artisan('incidents:diagnose')->expectsOutputToContain("[incoherent_status_log] Cadena discontinua: log anterior #{$previous->id} terminó en blocked; log #{$latest->id}")
        ->expectsOutputToContain('Hallazgos: 1.')->assertExitCode(1);
});
