<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Lista de asistencia</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #111827; }
        h1 { margin: 0 0 2px; font-size: 16px; }
        .excursion { margin: 0 0 2px; font-size: 12px; font-weight: bold; }
        .detalle { margin: 0 0 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #6b7280; padding: 7px 6px; text-align: left; }
        th { background-color: #e5e7eb; }
        tr { page-break-inside: avoid; }
        .centrado { text-align: center; }
        .casilla { display: inline-block; width: 13px; height: 13px; border: 1px solid #111827; }
        .vacio { padding: 24px; text-align: center; color: #4b5563; }
    </style>
</head>
<body>
    <h1>Lista de asistencia</h1>
    <p class="excursion">{{ $salida->excursion->nombre }}</p>
    <p class="detalle">
        Salida: {{ $salida->fecha_inicio->format('d/m/Y') }} a las {{ $salida->hora_de_salida->format('H:i') }}
        · Regreso: {{ $salida->fecha_fin->format('d/m/Y') }}
        · Participantes: {{ $participantes->count() }}
    </p>

    @if ($participantes->isEmpty())
        <p class="vacio">No hay participantes asociados a la Salida Programada aún.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th class="centrado" style="width: 5%">N.º</th>
                    <th style="width: 25%">Apellido</th>
                    <th style="width: 25%">Nombre</th>
                    <th style="width: 12%">DNI</th>
                    <th style="width: 10%">Reserva</th>
                    <th style="width: 14%">Requisito médico</th>
                    <th class="centrado" style="width: 9%">Presente</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($participantes as $participante)
                    <tr>
                        <td class="centrado">{{ $loop->iteration }}</td>
                        <td>{{ $participante->apellido }}</td>
                        <td>{{ $participante->nombre }}</td>
                        <td>{{ $participante->dni }}</td>
                        <td>R-{{ str_pad($participante->reserva_id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $participante->etiquetaRequisitoMedico() }}</td>
                        <td class="centrado"><span class="casilla"></span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
