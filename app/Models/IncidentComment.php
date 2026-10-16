<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\IncidentCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $incident_id
 * @property string $author_name
 * @property string $body
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Incident $incident
 */
#[Fillable(['incident_id', 'author_name', 'body'])]
class IncidentComment extends Model
{
    /** @use HasFactory<IncidentCommentFactory> */
    use HasFactory;

    /** @return BelongsTo<Incident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
