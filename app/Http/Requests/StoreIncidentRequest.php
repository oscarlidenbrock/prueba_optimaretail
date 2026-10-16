<?php

namespace App\Http\Requests;

use App\Enums\IncidentPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['bail', 'required', 'string', 'max:255'],
            'description' => ['bail', 'required', 'string', 'max:10000'],
            'priority' => ['bail', 'required', 'string', Rule::enum(IncidentPriority::class)],
            'requester_name' => ['bail', 'required', 'string', 'max:120'],
            'assigned_to' => ['bail', 'nullable', 'string', 'max:120'],
            'status' => ['missing'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.string' => 'El título debe ser texto.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los 10000 caracteres.',
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.string' => 'La prioridad debe ser texto.',
            'priority.enum' => 'La prioridad seleccionada no es válida.',
            'requester_name.required' => 'El solicitante es obligatorio.',
            'requester_name.string' => 'El solicitante debe ser texto.',
            'requester_name.max' => 'El solicitante no puede superar los 120 caracteres.',
            'assigned_to.string' => 'El responsable debe ser texto.',
            'assigned_to.max' => 'El responsable no puede superar los 120 caracteres.',
            'status.missing' => 'El estado inicial lo establece el servidor y no debe enviarse.',
        ];
    }
}
