<?php

namespace App\Http\Requests;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIncidentRequest extends FormRequest
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
            'status' => ['bail', 'nullable', 'string', Rule::enum(IncidentStatus::class)],
            'priority' => ['bail', 'nullable', 'string', Rule::enum(IncidentPriority::class)],
            'q' => ['bail', 'nullable', 'string', 'max:255'],
            'page' => ['bail', 'nullable', 'integer', 'min:1', 'max:2147483647'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.string' => 'El filtro de estado debe ser texto.',
            'status.enum' => 'El estado seleccionado no es válido.',
            'priority.string' => 'El filtro de prioridad debe ser texto.',
            'priority.enum' => 'La prioridad seleccionada no es válida.',
            'q.string' => 'La búsqueda debe ser texto.',
            'q.max' => 'La búsqueda no puede superar los 255 caracteres.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser mayor o igual que 1.',
            'page.max' => 'La página no puede superar 2147483647.',
        ];
    }
}
