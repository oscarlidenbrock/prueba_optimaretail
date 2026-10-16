<?php

namespace App\Http\Controllers;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Http\Requests\IndexIncidentRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Models\Incident;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function index(IndexIncidentRequest $request): Response
    {
        $status = $request->enum('status', IncidentStatus::class);
        $priority = $request->enum('priority', IncidentPriority::class);
        $search = $request->string('q')->toString();
        $filters = ['status' => $status?->value, 'priority' => $priority?->value, 'q' => $search];

        $incidents = Incident::query()
            ->matchingFilters($status, $priority, $search)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->appends($filters);

        return Inertia::render('incidents/index', [
            'incidents' => $incidents,
            'filters' => $filters,
        ]);
    }

    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        Incident::query()->create([
            ...$request->safe()->only(['title', 'description', 'priority', 'requester_name', 'assigned_to']),
            'status' => IncidentStatus::Open,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Incidencia creada correctamente.']);

        return to_route('incidents.index');
    }
}
