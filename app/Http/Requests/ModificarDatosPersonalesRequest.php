<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU 13 - Modificar datos personales (RF-13).
 *
 * Solo son modificables el email, el teléfono, el nombre y el apellido.
 * El DNI y la fecha de nacimiento no se modifican.
 */
class ModificarDatosPersonalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:strict',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->user()->id),
            ],
            'telefono' => ['required', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'apellido' => 'apellido',
            'email' => 'correo electrónico',
            'telefono' => 'teléfono',
        ];
    }

    /**
     * Mensajes en español (el proyecto no tiene archivos de idioma).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Completá el campo :attribute.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede superar los :max caracteres.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'email.lowercase' => 'El correo electrónico debe estar escrito en minúsculas.',
            'email.unique' => 'Ese correo electrónico ya está en uso por otra cuenta.',
        ];
    }

    /**
     * Datos normalizados para actualizar la ficha del cliente.
     *
     * @return array<string, string>
     */
    public function datos(): array
    {
        return [
            'nombre' => (string) $this->input('nombre'),
            'apellido' => (string) $this->input('apellido'),
            'email' => (string) $this->input('email'),
            'telefono' => (string) $this->input('telefono'),
        ];
    }
}
