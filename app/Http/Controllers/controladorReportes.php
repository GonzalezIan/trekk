<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerarReportePeriodicoRequest;
use App\Services\GestionReportes;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU 27 - Generar reporte periódico.
 */
class ControladorReportes extends Controller
{
    public function __construct(
        private readonly GestionReportes $gestionReportes,
    ) {}

    /**
     * Pantalla de generación del reporte.
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('Admin/Reportes/Periodico', [
            'reporte' => null,
            'periodo' => null,
        ]);
    }

    /**
     * Genera el reporte del período solicitado.
     */
    public function generar(GenerarReportePeriodicoRequest $request): InertiaResponse
    {
        $reporte = $this->gestionReportes->generarReportePeriodico(
            (string) $request->input('fecha_inicio'),
            (string) $request->input('fecha_fin'),
        );

        if ($reporte['total_reservas'] === 0) {
            // Flujo alternativo 3.3 del CU 27, con su texto literal.
            return Inertia::render('Admin/Reportes/Periodico', [
                'reporte' => null,
                'periodo' => null,
                'error' => 'No se encontraron datos para el período seleccionado.',
            ]);
        }

        return Inertia::render('Admin/Reportes/Periodico', [
            'reporte' => $reporte,
            'periodo' => $reporte['periodo'],
        ]);
    }

    /**
     * Exporta el reporte en CSV (CU 27, paso 9).
     */
    public function exportar(GenerarReportePeriodicoRequest $request): StreamedResponse
    {
        $reporte = $this->gestionReportes->generarReportePeriodico(
            (string) $request->input('fecha_inicio'),
            (string) $request->input('fecha_fin'),
        );

        $nombre = config('trekk.archivo_exportacion_reportes', 'reporte-periodico.csv');

        return response()->streamDownload(function () use ($reporte): void {
            $archivo = fopen('php://output', 'w');

            fputcsv($archivo, ['Reporte de clientes frecuentes']);
            fputcsv($archivo, ['Nombre', 'Apellido', 'DNI', 'Excursiones realizadas']);
            foreach ($reporte['clientes_frecuentes'] as $cliente) {
                fputcsv($archivo, [
                    $cliente['nombre'],
                    $cliente['apellido'],
                    $cliente['dni'],
                    $cliente['excursiones'],
                ]);
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
}
