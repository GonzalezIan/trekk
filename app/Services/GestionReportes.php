<?php

namespace App\Services;

use App\Models\Conformidad;
use App\Models\Reserva;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Paquete de análisis «Reportes» (CU 27).
 *
 * Los tres reportes consideran únicamente las reservas en estado Realizada cuya
 * Salida Programada asociada tenga fecha de fin dentro del período (CU 27, RN-01).
 */
class GestionReportes
{
    /**
     * Genera el reporte periódico (CU 27).
     *
     * @return array{
     *     periodo: array{desde: string, hasta: string},
     *     clientes_frecuentes: array<int, array<string, mixed>>,
     *     actividades_frecuentes: array<int, array<string, mixed>>,
     *     conformidad: array<int, array<string, mixed>>,
     *     total_reservas: int
     * }
     */
    public function generarReportePeriodico(Carbon|string $fechaInicio, Carbon|string $fechaFin): array
    {
        $desde = $fechaInicio instanceof Carbon ? $fechaInicio : Carbon::parse($fechaInicio);
        $hasta = $fechaFin instanceof Carbon ? $fechaFin : Carbon::parse($fechaFin);

        $reservas = Reserva::listarRealizadas($desde, $hasta);

        return [
            'periodo' => [
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
            ],
            'clientes_frecuentes' => $this->calcularClientesFrecuentes($reservas),
            'actividades_frecuentes' => $this->calcularActividadesFrecuentes($reservas),
            'conformidad' => $this->calcularConformidad($reservas),
            'total_reservas' => $reservas->count(),
        ];
    }

    /**
     * Reporte de clientes frecuentes (CU 27, RN-02).
     *
     * Nombre, apellido, DNI y cantidad de excursiones realizadas, de mayor a menor.
     *
     * @param  Collection<int, Reserva>  $reservas
     * @return array<int, array<string, mixed>>
     */
    public function calcularClientesFrecuentes($reservas): array
    {
        $clientes = [];

        foreach ($reservas as $reserva) {
            $cliente = $reserva->cliente;

            if ($cliente === null) {
                continue;
            }

            $dni = $cliente->dni;

            if (! isset($clientes[$dni])) {
                $clientes[$dni] = [
                    'nombre' => $cliente->nombre,
                    'apellido' => $cliente->apellido,
                    'dni' => $dni,
                    'excursiones' => 0,
                ];
            }

            $clientes[$dni]['excursiones']++;
        }

        $resultado = array_values($clientes);

        usort($resultado, fn (array $a, array $b) => $b['excursiones'] <=> $a['excursiones']);

        return $resultado;
    }

    /**
     * Reporte de actividades frecuentes (CU 27, RN-03).
     *
     * Por excursión, la cantidad total de participantes, de mayor a menor.
     *
     * @param  Collection<int, Reserva>  $reservas
     * @return array<int, array<string, mixed>>
     */
    public function calcularActividadesFrecuentes($reservas): array
    {
        $excursiones = [];

        foreach ($reservas as $reserva) {
            $excursion = $reserva->paquete?->salidaProgramada?->excursion;

            if ($excursion === null) {
                continue;
            }

            $id = $excursion->id;

            if (! isset($excursiones[$id])) {
                $excursiones[$id] = [
                    'excursion' => $excursion->nombre,
                    'participantes' => 0,
                ];
            }

            $excursiones[$id]['participantes'] += (int) $reserva->participantes()->count();
        }

        $resultado = array_values($excursiones);

        usort($resultado, fn (array $a, array $b) => $b['participantes'] <=> $a['participantes']);

        return $resultado;
    }

    /**
     * Reporte de conformidad (CU 27, RN-04).
     *
     * Por excursión: valoración promedio (1 a 5), cantidad de valoraciones y el
     * detalle de los comentarios, de mayor a menor promedio.
     *
     * @param  Collection<int, Reserva>  $reservas
     * @return array<int, array<string, mixed>>
     */
    public function calcularConformidad($reservas): array
    {
        $excursiones = [];

        foreach ($reservas as $reserva) {
            $conformidad = $reserva->conformidad;

            if ($conformidad === null) {
                continue;
            }

            $excursion = $reserva->paquete?->salidaProgramada?->excursion;

            if ($excursion === null) {
                continue;
            }

            $id = $excursion->id;

            if (! isset($excursiones[$id])) {
                $excursiones[$id] = [
                    'excursion' => $excursion->nombre,
                    'valoracion_promedio' => 0.0,
                    'valoraciones' => 0,
                    'comentarios' => [],
                ];
            }

            $excursiones[$id]['valoraciones']++;
            $excursiones[$id]['valoracion_promedio'] += (int) $conformidad->valoracion;

            if ($conformidad->comentario !== null && $conformidad->comentario !== '') {
                $excursiones[$id]['comentarios'][] = [
                    'cliente' => ($reserva->cliente?->nombre ?? '').' '.($reserva->cliente?->apellido ?? ''),
                    'valoracion' => (int) $conformidad->valoracion,
                    'comentario' => $conformidad->comentario,
                ];
            }
        }

        $resultado = [];

        foreach ($excursiones as $datos) {
            $datos['valoracion_promedio'] = round(
                $datos['valoracion_promedio'] / max(1, $datos['valoraciones']),
                2,
            );

            $resultado[] = $datos;
        }

        usort($resultado, fn (array $a, array $b) => $b['valoracion_promedio'] <=> $a['valoracion_promedio']);

        return $resultado;
    }
}
