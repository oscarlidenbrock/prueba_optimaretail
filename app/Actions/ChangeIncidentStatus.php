<?php

namespace App\Actions;

use App\Enums\IncidentStatus;
use App\Exceptions\IncidentStatusConflict;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ChangeIncidentStatus
{
    /**
     * Persist a status change and its audit entry atomically using the locked database state.
     * The supplied instance is only used for its ID; the returned instance holds the new state.
     *
     * @throws IncidentStatusConflict
     * @throws ValidationException
     */
    public function handle(Incident $incident, IncidentStatus $newStatus, string $actorName): Incident
    {
        $actorName = trim($actorName);

        Validator::make(['actor_name' => $actorName], [
            'actor_name' => ['required', 'max:120'],
        ], [
            'actor_name.required' => 'El nombre del actor es obligatorio.',
            'actor_name.max' => 'El nombre del actor no puede superar los 120 caracteres.',
        ])->validate();

        return DB::transaction(function () use ($incident, $newStatus, $actorName): Incident {
            $lockedIncident = Incident::query()->whereKey($incident->getKey())->lockForUpdate()->firstOrFail();
            $oldStatus = $lockedIncident->status;

            if (! $oldStatus->canTransitionTo($newStatus)) {
                throw new IncidentStatusConflict;
            }

            $lockedIncident->status = $newStatus;

            if (! $lockedIncident->save()) {
                throw new RuntimeException('Unable to persist the incident status.');
            }

            $log = $lockedIncident->logs()->create([
                'action' => 'status_changed',
                'old_value' => $oldStatus->value,
                'new_value' => $newStatus->value,
                'user_name' => $actorName,
            ]);

            if (! $log->exists) {
                throw new RuntimeException('Unable to persist the incident status log.');
            }

            return $lockedIncident;
        });
    }
}
