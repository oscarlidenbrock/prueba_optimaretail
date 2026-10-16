<?php

namespace App\Http\Requests;

use App\Enums\IncidentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentStatusRequest extends FormRequest
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
        $contextRules = [];

        foreach ((new IndexIncidentRequest)->rules() as $field => $rules) {
            $contextRules['list_context.'.$field] = $rules;
        }

        return [
            'status' => ['bail', 'required', 'string', Rule::enum(IncidentStatus::class)],
            'list_context' => ['sometimes', 'array:status,priority,q,page'],
            ...$contextRules,
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $contextMessages = [];

        foreach ((new IndexIncidentRequest)->messages() as $rule => $message) {
            $contextMessages['list_context.'.$rule] = $message;
        }

        return [
            'status.required' => 'El nuevo estado es obligatorio.',
            'status.string' => 'El nuevo estado debe ser texto.',
            'status.enum' => 'El estado solicitado no es válido.',
            'list_context.array' => 'El contexto del listado solo admite estado, prioridad, búsqueda y página.',
            ...$contextMessages,
        ];
    }
}
