<?php

namespace App\Console\Commands;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Formatter\OutputFormatter;

#[Signature('incidents:diagnose {--dry-run : Mostrar el plan sin escrituras (predeterminado)} {--apply : Aplicar solo reparaciones justificadas; los casos actuales requieren revisión manual}')]
#[Description('Diagnostica inconsistencias y evalúa reparaciones sin resolver casos ambiguos')]
class DiagnoseIncidents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Las opciones --apply y --dry-run no pueden combinarse. No se ha ejecutado el diagnóstico ni modificado datos.');

            return self::INVALID;
        }

        $apply = (bool) $this->option('apply');
        $this->info($apply
            ? 'Modo apply: evaluar únicamente reparaciones con evidencia suficiente.'
            : 'Diagnóstico de incidencias — solo lectura (dry-run).');
        $scanned = 0;
        $findings = 0;

        Incident::query()->select(['id', 'status'])
            ->with(['logs' => fn ($query) => $query->where('action', 'status_changed')])
            ->chunkById(100, function (Collection $incidents) use (&$scanned, &$findings): void {
                foreach ($incidents as $incident) {
                    $scanned++;

                    foreach ($this->findingsFor($incident) as $finding) {
                        $findings++;
                        $this->line(OutputFormatter::escape("Incidencia #{$incident->id} [{$finding['problem']}] {$finding['evidence']}"));
                        $this->line('  Revisión manual: '.$this->manualRecommendation($finding['problem']));
                    }
                }
            });

        if ($findings === 0) {
            $this->info('No se detectaron inconsistencias.');
        } else {
            $this->warn('Revisión manual requerida: contrastar estado e historial; ninguno se considera automáticamente autoritativo.');
        }

        // Neither a fixture marker nor a plausible log proves which historical facts are correct.
        // No current finding qualifies for an automatic write, even with explicit --apply.
        $this->line('Reparaciones automáticas justificadas: 0. Correcciones aplicadas: 0.');
        $this->line("Hallazgos pendientes de revisión manual: {$findings}.");
        $this->line($apply
            ? 'No se aplicaron cambios: ningún caso dispone de evidencia suficiente para una reparación automática.'
            : 'Se modificaría: nada. Las propuestas requieren una decisión humana documentada.');
        $this->line("Incidencias revisadas: {$scanned}. Hallazgos: {$findings}. Ningún dato modificado.");

        return $findings === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function manualRecommendation(string $problem): string
    {
        return match ($problem) {
            'resolved_without_resolution_log' => 'Confirmar la resolución y su evidencia; no inventar el cambio, actor o fecha histórica que faltan.',
            'status_mismatch' => 'Contrastar estado e historial con evidencia externa y documentar cuál debe prevalecer antes de reconciliar.',
            default => 'Determinar si hubo duplicación o un cambio real mal registrado; conservar los logs originales y documentar la decisión.',
        };
    }

    /** @return list<array{problem: string, evidence: string}> */
    private function findingsFor(Incident $incident): array
    {
        $findings = [];
        // Raw status keeps diagnosis usable even if stored values cannot be cast to the enum.
        $status = (string) $incident->getRawOriginal('status');
        $logs = $incident->logs;
        $latest = $logs->last();

        if ($status === IncidentStatus::Resolved->value && ! $logs->contains(fn (IncidentLog $log): bool => $log->new_value === IncidentStatus::Resolved->value)) {
            $findings[] = ['problem' => 'resolved_without_resolution_log', 'evidence' => 'Estado actual=resolved; no existe status_changed con new_value=resolved.'];
        }

        if ($latest !== null && $latest->new_value !== $status) {
            $findings[] = ['problem' => 'status_mismatch', 'evidence' => "Estado actual={$status}; último ".$this->logEvidence($latest)];
        }

        /** @var array<string, int> $seen */
        $seen = [];
        $previous = null;

        // The relationship orders by created_at ASC, id ASC; its last item is the latest change.
        foreach ($logs as $log) {
            $key = serialize([$log->old_value, $log->new_value]);
            $oldStatus = IncidentStatus::tryFrom($log->old_value ?? '');
            $newStatus = IncidentStatus::tryFrom($log->new_value ?? '');
            $reason = null;

            if (isset($seen[$key])) {
                $reason = "Par de estados duplicado del log #{$seen[$key]}";
            } elseif ($oldStatus === null || $newStatus === null || ! $oldStatus->canTransitionTo($newStatus)) {
                $reason = 'Valores nulos/desconocidos o transición no permitida';
            } elseif ($previous !== null && $previous->new_value !== $log->old_value) {
                $reason = "Cadena discontinua: log anterior #{$previous->id} terminó en ".($previous->new_value ?? 'NULL');
            }

            if ($reason !== null) {
                $findings[] = ['problem' => 'incoherent_status_log', 'evidence' => "{$reason}; ".$this->logEvidence($log)];
            }

            $seen[$key] ??= $log->id;
            $previous = $log;
        }

        return $findings;
    }

    private function logEvidence(IncidentLog $log): string
    {
        return "log #{$log->id}: ".($log->old_value ?? 'NULL').' -> '.($log->new_value ?? 'NULL')
            ."; actor={$log->user_name}; fecha=".$log->created_at->toDateTimeString().' UTC';
    }
}
