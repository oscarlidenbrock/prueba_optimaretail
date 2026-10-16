<?php

namespace App\Models;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Carbon\CarbonInterface;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property IncidentStatus $status
 * @property IncidentPriority $priority
 * @property string $requester_name
 * @property string|null $assigned_to
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, IncidentLog> $logs
 * @property-read Collection<int, IncidentComment> $comments
 */
#[Fillable(['title', 'description', 'status', 'priority', 'requester_name', 'assigned_to'])]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    /** @return HasMany<IncidentLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(IncidentLog::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<IncidentComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'priority' => IncidentPriority::class,
        ];
    }
}
