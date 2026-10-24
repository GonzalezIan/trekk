<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // CU 28 / RF-30: correo electrónico o nombre de usuario
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }


    /**
     * Mensajes de los datos obligatorios.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Ingresá tu correo electrónico o nombre de usuario.',
            'password.required' => 'Ingresá tu contraseña.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Si lo ingresado tiene formato de correo se busca por email; si no, por nombre de usuario
        $login = Str::lower(trim($this->string('login')));
        $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'usuario';

        if (! Auth::attempt([$campo => $login, 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'El usuario o la contraseña no son correctos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited je.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request owo.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}