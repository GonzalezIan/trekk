<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // El nombre de usuario se guarda sin espacios y en minúsculas, igual que se busca en el login
        $request->merge(['usuario' => Str::lower(trim((string) $request->usuario))]);

        $request->validate([
            'name' => 'required|string|max:255',
            // RF-12: nombre de usuario obligatorio y único
            'usuario' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/', 'unique:'.User::class],
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'usuario.required' => 'Ingresá un nombre de usuario.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'usuario.max' => 'El nombre de usuario no puede superar los 30 caracteres.',
            'usuario.regex' => 'El nombre de usuario solo puede tener letras, números, puntos, guiones y guiones bajos (sin espacios ni tildes).',
            'usuario.unique' => 'Ese nombre de usuario ya está en uso.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'usuario' => $request->usuario,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
