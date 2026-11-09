<?php

namespace App\Http\Requests;

use App\Models\Cliente;
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

    /** Igual que en el registro (CU 12): el correo en minúsculas y sin espacios, y el teléfono sin separadores. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'telefono' => Cliente::normalizarTelefono($this->input('telefono')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80','regex:/^[\pL\s\-]+$/u'],
            'apellido' => ['required', 'string', 'max:80','regex:/^[\pL\s\-]+$/u'],
            'email' => [
                'required',
                'string',
                'email:strict',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->user()->id),
            ],
            // Misma regla que en el registro (CU 12)
            'telefono' => ['required', 'string', 'regex:' . Cliente::REGEX_TELEFONO],
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

    public function messages(): array
    {
        return [
            'required' => 'Completá el campo :attribute.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede superar los :max caracteres.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'telefono.regex' => Cliente::MENSAJE_TELEFONO,
            'email.unique' => 'Ese correo electrónico ya está en uso por otra cuenta.',
            'nombre.regex'=>'El nombre no puede contener números ni símbolos',
            'apellido.regex'=>'El apellido no puede contener números ni símbolos'
        ];
    }

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
