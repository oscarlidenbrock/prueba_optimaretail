<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentCommentRequest extends FormRequest
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
            'body' => ['bail', 'required', 'string', 'max:10000'],
            'author_name' => ['bail', 'required', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Replace client input before validation: authorship belongs exclusively to the session.
        $this->merge(['author_name' => $this->user()?->name]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'body.required' => 'El comentario es obligatorio y no puede estar vacío.',
            'body.string' => 'El comentario debe ser texto.',
            'body.max' => 'El comentario no puede superar los 10000 caracteres.',
            'author_name.required' => 'El usuario autenticado debe tener un nombre válido para comentar.',
            'author_name.string' => 'El nombre del usuario autenticado debe ser texto.',
            'author_name.max' => 'El nombre del usuario autenticado no puede superar los 120 caracteres para comentar.',
        ];
    }
}
