<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

    /**
     * Modifica los datos personales del cliente (CU 13, RF-13).
     *
     * Solo son modificables el email, el teléfono, el nombre y el apellido.
     * El DNI y la fecha de nacimiento no se modifican.
     *
     * @param  array{email?: string, telefono?: string, nombre?: string, apellido?: string}  $datos
     */

class GestionUsuarios
{

    public function modificarDatosPersonales(Cliente $cliente, array $datos): Cliente
    {
        return DB::transaction(function () use ($cliente, $datos): Cliente {
            $atributos = array_intersect_key($datos, array_flip(['nombre', 'apellido', 'telefono']));

            if ($atributos !== []) {
                $cliente->update($atributos);
            }

            $cambiosDeUsuario = [];

            if (array_key_exists('email', $datos)) {
                $cambiosDeUsuario['email'] = $datos['email'];
            }

            if (array_key_exists('nombre', $atributos) || array_key_exists('apellido', $atributos)) {
                $cambiosDeUsuario['name'] = $cliente->refresh()->nombreCompleto();
            }

            if ($cambiosDeUsuario !== []) {
                $cliente->user->update($cambiosDeUsuario);
            }

            return $cliente->refresh();
        });
    }
}
