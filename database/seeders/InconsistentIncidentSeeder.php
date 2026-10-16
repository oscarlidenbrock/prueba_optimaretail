<?php

namespace Database\Seeders;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InconsistentIncidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Keep fixtures and their evidence atomic; never reset an existing fixture.
        DB::transaction(function (): void {
            $cases = [
                'resolved_without_resolution_log' => ['Resuelta sin log de resolución', IncidentStatus::Resolved, 0],
                'status_mismatch' => ['Estado distinto del último cambio', IncidentStatus::Blocked, 1],
                'duplicate_status_log' => ['Cambio de estado duplicado', IncidentStatus::InReview, 2],
            ];

            foreach ($cases as $key => [$label, $status, $logCount]) {
                $incident = Incident::query()->firstOrCreate([
                    'description' => "[SIMULACIÓN diagnóstico:v1:{$key}] Caso artificial para diagnóstico; no representa una incidencia real.",
                    'requester_name' => 'Simulación de diagnóstico',
                ], [
                    'title' => "[SIMULACIÓN] {$label}",
                    'status' => $status,
                    'priority' => IncidentPriority::Low,
                    'assigned_to' => null,
                ]);

                if (! $incident->wasRecentlyCreated) {
                    continue;
                }

                for ($index = 0; $index < $logCount; $index++) {
                    $incident->logs()->create([
                        'action' => 'status_changed',
                        'old_value' => IncidentStatus::Open->value,
                        'new_value' => IncidentStatus::InReview->value,
                        'user_name' => 'Simulación de diagnóstico',
                        'metadata' => ['simulation' => true, 'fixture' => "diagnosis:v1:{$key}"],
                    ]);
                }
            }
        });
    }
}
