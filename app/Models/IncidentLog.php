<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\IncidentLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $incident_id
 * @property string $action
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string $user_name
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface $created_at
 * @property-read Incident $incident
 */
#[Fillable(['incident_id', 'action', 'old_value', 'new_value', 'user_name', 'metadata'])]
class IncidentLog extends Model
{
    // Audit entries have a creation time, never an automatic modification time.
    public const UPDATED_AT = null;

    /** @use HasFactory<IncidentLogFactory> */
    use HasFactory;

    /** @return BelongsTo<Incident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
