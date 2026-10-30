<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerarReportePeriodicoRequest;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU 27 - Generar reporte periódico (solo Administrativo).
 */
class ControladorReportes extends Controller
{
    /**
     * CU 27 - pasos 1 y 2. Pantalla Generar reporte periódico, que solicita el período.
     */
    public function mostrarPantallaReporte(): InertiaResponse
    {
        return Inertia::render('Admin/Reportes/Periodico', [
            'reporte' => null,
        ]);
    }

    /**
     * CU 27 - pasos 3 a 9. DS: generarReportePeriodico(fechaInicio, fechaFin)
     *     -> clientesFrecuentes, actividadesFrecuentes, conformidad.
     * Las fechas ya llegan validadas (3.1 y 3.2, GenerarReportePeriodicoRequest).
     */
    public function generarReportePeriodico(GenerarReportePeriodicoRequest $request): InertiaResponse
    {
        $reporte = $this->calcularReporte($request->input('fecha_inicio'), $request->input('fecha_fin'));

        // 3.3: no hay reservas "Realizada" en el período
        if ($reporte['total_reservas'] === 0) {
            return Inertia::render('Admin/Reportes/Periodico', [
                'reporte' => null,
                'error' => 'No se encontraron datos para el período seleccionado.',
            ]);
        }

        return Inertia::render('Admin/Reportes/Periodico', [
            'reporte' => $reporte,
        ]);
    }

    /**
     * CU 27 - paso 10. Exporta el reporte generado en CSV.
     */
    public function exportarReporte(GenerarReportePeriodicoRequest $request): StreamedResponse
    {
        $reporte = $this->calcularReporte($request->input('fecha_inicio'), $request->input('fecha_fin'));
        $nombre = "reporte-periodico-{$reporte['periodo']['desde']}-al-{$reporte['periodo']['hasta']}.csv";

        return response()->streamDownload(function () use ($reporte): void {
            $archivo = fopen('php://output', 'w');
            // BOM: así Excel muestra bien los acentos
            fwrite($archivo, "\xEF\xBB\xBF");

            fputcsv($archivo, ['Reporte de clientes frecuentes']);
            fputcsv($archivo, ['Nombre', 'Apellido', 'DNI', 'Reservas realizadas']);
            foreach ($reporte['clientes_frecuentes'] as $cliente) {
                fputcsv($archivo, [$cliente['nombre'], $cliente['apellido'], $cliente['dni'], $cliente['reservas']]);
            }

            fputcsv($archivo, []);
            fputcsv($archivo, ['Reporte de actividades frecuentes']);
            fputcsv($archivo, ['Excursión', 'Participantes']);
            foreach ($reporte['actividades_frecuentes'] as $actividad) {
                fputcsv($archivo, [$actividad['excursion'], $actividad['participantes']]);
            }

            fputcsv($archivo, []);
            fputcsv($archivo, ['Reporte de conformidad']);
            fputcsv($archivo, ['Excursión', 'Valoración promedio', 'Cantidad de valoraciones', 'Comentarios']);
            foreach ($reporte['conformidad'] as $conformidad) {
                fputcsv($archivo, [
                    $conformidad['excursion'],
                    $conformidad['valoracion_promedio'],
                    $conformidad['valoraciones'],
                    implode(' | ', array_column($conformidad['comentarios'], 'comentario')),
                ]);
            }

            fclose($archivo);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * DS CU 27 pasos 2 a 16: listarRealizadas(fechaInicio, fechaFin) y los tres reportes.
     */
    private function calcularReporte(string $fechaInicio, string $fechaFin): array
    {
        $reservas = Reserva::listarRealizadas($fechaInicio, $fechaFin);

        return [
            'periodo' => ['desde' => $fechaInicio, 'hasta' => $fechaFin],
            'clientes_frecuentes' => $this->calcularClientesFrecuentes($reservas),
            'actividades_frecuentes' => $this->calcularActividadesFrecuentes($reservas),
            'conformidad' => $this->calcularConformidad($reservas),
            'total_reservas' => $reservas->count(),
        ];
    }

    /**
     * DS CU 27 pasos 4 a 6: calcularClientesFrecuentes(reservas) -> loop getCliente().
     * RN-02: nombre, apellido, DNI y cantidad de reservas realizadas, de mayor a menor.
     */
    private function calcularClientesFrecuentes(Collection $reservas): array
    {
        return $reservas
            ->groupBy(fn (Reserva $reserva) => $reserva->getCliente()->dni)
            ->map(function (Collection $reservasDelCliente) {
                $cliente = $reservasDelCliente->first()->getCliente();

                return [
                    'nombre' => $cliente->nombre,
                    'apellido' => $cliente->apellido,
                    'dni' => $cliente->dni,
                    'reservas' => $reservasDelCliente->count(),
                ];
            })
            ->sortByDesc('reservas')
            ->values()
            ->all();
    }

    /**
     * DS CU 27 pasos 7 a 11: calcularActividadesFrecuentes(reservas)
     *     -> loop getExcursion() y getCantidadParticipantes().
     * RN-03: por excursión, la cantidad total de participantes, de mayor a menor.
     */
    private function calcularActividadesFrecuentes(Collection $reservas): array
    {
        return $reservas
            ->groupBy(fn (Reserva $reserva) => $reserva->getExcursion()->id)
            ->map(fn (Collection $reservasDeLaExcursion) => [
                'excursion' => $reservasDeLaExcursion->first()->getExcursion()->nombre,
                'participantes' => $reservasDeLaExcursion->sum(fn (Reserva $reserva) => $reserva->getCantidadParticipantes()),
            ])
            ->sortByDesc('participantes')
            ->values()
            ->all();
    }

    /**
     * DS CU 27 pasos 12 a 16: calcularConformidad(reservas) -> loop getExcursion() y getConformidad().
     * RN-04: por excursión, valoración promedio (1 a 5), cantidad de valoraciones y comentarios,
     * de mayor a menor promedio. Solo cuentan las reservas con conformidad registrada (CU 20).
     */
    private function calcularConformidad(Collection $reservas): array
    {
        return $reservas
            ->filter(fn (Reserva $reserva) => $reserva->getConformidad() !== null)
            ->groupBy(fn (Reserva $reserva) => $reserva->getExcursion()->id)
            ->map(fn (Collection $reservasDeLaExcursion) => [
                'excursion' => $reservasDeLaExcursion->first()->getExcursion()->nombre,
                'valoracion_promedio' => round(
                    $reservasDeLaExcursion->avg(fn (Reserva $reserva) => $reserva->getConformidad()->valoracion), 2),
                'valoraciones' => $reservasDeLaExcursion->count(),
                'comentarios' => $reservasDeLaExcursion
                    ->filter(fn (Reserva $reserva) => filled($reserva->getConformidad()->comentario))
                    ->map(fn (Reserva $reserva) => [
                        'cliente' => $reserva->getCliente()->nombre.' '.$reserva->getCliente()->apellido,
                        'valoracion' => (int) $reserva->getConformidad()->valoracion,
                        'comentario' => $reserva->getConformidad()->comentario,
                    ])
                    ->values()
                    ->all(),
            ])
            ->sortByDesc('valoracion_promedio')
            ->values()
            ->all();
    }
}
