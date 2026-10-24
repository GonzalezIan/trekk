<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        // Se normaliza antes de validar: usuario y correo en minúsculas (como se buscan en el login),
        // y el DNI solo con números (acepta "12.345.678")
        $request->merge([
            'usuario' => Str::lower(trim((string) $request->usuario)),
            'email' => Str::lower(trim((string) $request->email)),
            'dni' => preg_replace('/\D/', '', (string) $request->dni),
        ]);

        $mayorDeEdad = now()->subYears(Cliente::EDAD_MINIMA)->toDateString();

        // RF-12: todos los datos son obligatorios
        $datos = $request->validate([
            'dni' => ['required', 'digits_between:7,8', 'unique:clientes,dni'],
            'nombre' => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'after:1900-01-01', 'before_or_equal:' . $mayorDeEdad],
            'telefono' => ['required', 'string', 'regex:/^[0-9 +()-]{6,30}$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'usuario' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/', 'unique:' . User::class],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'required' => 'Este dato es obligatorio.',
            'dni.digits_between' => 'El DNI debe tener 7 u 8 números.',
            'dni.unique' => 'Ya existe un cliente registrado con ese DNI.',
            'nombre.max' => 'El nombre no puede superar los 80 caracteres.',
            'apellido.max' => 'El apellido no puede superar los 80 caracteres.',
            'fecha_nacimiento.date_format' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.after' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before_or_equal' => 'Tenés que ser mayor de 18 años para registrarte.',
            'telefono.regex' => 'El teléfono solo puede tener números, espacios, +, - y paréntesis.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Ya existe una cuenta con ese correo electrónico.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'usuario.max' => 'El nombre de usuario no puede superar los 30 caracteres.',
            'usuario.regex' => 'El nombre de usuario solo puede tener letras, números, puntos, guiones y guiones bajos (sin espacios ni tildes).',
            'usuario.unique' => 'Ese nombre de usuario ya está en uso.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = Cliente::registrar($datos)->user;

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
