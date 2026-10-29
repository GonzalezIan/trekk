<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModificarDatosPersonalesRequest;
use App\Models\Cliente;
use App\Services\GestionUsuarios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;


class controladorClientes extends Controller
{
    public function __construct(
        private readonly GestionUsuarios $gestionUsuarios,
    ) {}

    /**
     * Muestra «Mis datos»: el perfil del cliente autenticado (CU 13).
     */
    public function edit(Request $request): Response
    {
        $cliente = $this->clienteAutenticado($request);

        return Inertia::render('Perfil/Editar', [
            'cliente' => [
                'dni' => $cliente->dni,
                'nombre' => $cliente->nombre,
                'apellido' => $cliente->apellido,
                'fechaNacimiento' => $cliente->fecha_nacimiento?->format('d/m/Y'),
                'telefono' => $cliente->telefono,
                'email' => $request->user()->email,
            ],
        ]);
    }

    /**
     * Actualiza los datos personales del cliente (CU 13, RF-13).
     */
    public function update(ModificarDatosPersonalesRequest $request): RedirectResponse
    {
        $cliente = $this->clienteAutenticado($request);

        $this->gestionUsuarios->modificarDatosPersonales($cliente, $request->datos());

        return back()->with('exito', 'Tus datos personales fueron actualizados correctamente.');
    }

    /**
     * Ficha de negocio del cliente autenticado (el administrativo no tiene una).
     */
    private function clienteAutenticado(Request $request): Cliente
    {
        $cliente = $request->user()->cliente;

        abort_if($cliente === null, 403, 'El usuario autenticado no tiene una ficha de cliente.');

        return $cliente;
    }
}
