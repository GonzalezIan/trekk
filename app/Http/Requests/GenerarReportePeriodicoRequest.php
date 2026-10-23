<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU 27 - Generar reporte periódico.
 *
 * El período es obligatorio y la fecha de inicio debe ser anterior o igual a
 * la fecha de fin (flujos alternativos 3.1 y 3.2).
 */
class GenerarReportePeriodicoRequest extends FormRequest
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
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_inicio.required' => 'Debe ingresar ambas fechas del período a consultar.',
            'fecha_fin.required' => 'Debe ingresar ambas fechas del período a consultar.',
            'fecha_fin.after_or_equal' => 'La fecha de inicio debe ser anterior o igual a la fecha de fin.',
        ];
    }
}
