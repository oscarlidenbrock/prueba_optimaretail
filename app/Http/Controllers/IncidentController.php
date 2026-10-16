<?php

namespace App\Http\Controllers;

use App\Actions\ChangeIncidentStatus;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Exceptions\IncidentStatusConflict;
use App\Http\Requests\IndexIncidentRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Requests\UpdateIncidentStatusRequest;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function show(Incident $incident): Response
    {
        $incident->load([
            'logs' => fn ($query) => $query->reorder()->orderByDesc('created_at')->orderByDesc('id'),
            'comments',
        ]);

        return Inertia::render('incidents/show', [
            'incident' => $incident,
            'allowedTransitions' => array_map(
                fn (IncidentStatus $status): string => $status->value,
                $incident->status->allowedTransitions(),
            ),
        ]);
    }

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
            ->appends($filters)
            ->through(fn (Incident $incident): array => [
                ...$incident->toArray(),
                'allowed_transitions' => array_map(
                    fn (IncidentStatus $status): string => $status->value,
                    $incident->status->allowedTransitions(),
                ),
            ]);

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

    public function updateStatus(
        UpdateIncidentStatusRequest $request,
        Incident $incident,
        ChangeIncidentStatus $changeIncidentStatus,
    ): RedirectResponse|JsonResponse {
        $redirect = $request->has('list_context')
            ? to_route('incidents.index', $request->validated('list_context', []), 303)
            : to_route('incidents.show', $incident, 303);

        try {
            $changeIncidentStatus->handle(
                $incident,
                IncidentStatus::from($request->string('status')->toString()),
                $request->user()->name,
            );
        } catch (IncidentStatusConflict $exception) {
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'code' => 'incident_status_conflict',
                    'errors' => ['status' => [$exception->getMessage()]],
                ], 409);
            }

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
                'code' => 'incident_status_conflict',
            ]);

            return $redirect->withErrors(['status' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Estado de la incidencia actualizado correctamente.',
        ]);

        return $redirect;
    }
}
