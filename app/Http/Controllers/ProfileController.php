<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Pantalla para que el Administrativo cambie su contraseña (PUT password.update).
     * El cliente la cambia desde «Mis datos» (CU 13).
     */
    public function edit(): Response
    {
        return Inertia::render('Profile/Edit');
    }
}
