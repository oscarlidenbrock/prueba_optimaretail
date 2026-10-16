<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentCommentRequest;
use App\Models\Incident;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class IncidentCommentController extends Controller
{
    public function store(StoreIncidentCommentRequest $request, Incident $incident): RedirectResponse
    {
        $incident->comments()->create($request->safe()->only(['body', 'author_name']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comentario añadido correctamente.']);

        return to_route('incidents.show', $incident, 303);
    }
}
