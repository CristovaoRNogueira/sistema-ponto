<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
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
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->input('login');
        $password = $this->input('password');

        // LÓGICA INTELIGENTE DE LOGIN (EMAIL OU CPF)
        $isAuthenticated = false;

        // 1. Tenta validar como E-mail
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(['email' => $login, 'password' => $password], $this->boolean('remember'))) {
                $isAuthenticated = true;
            }
        } else {
            // 2. Se não é e-mail, assume que é CPF

            // Opção A: Tenta exatamente como o utilizador digitou
            if (!$isAuthenticated && Auth::attempt(['cpf' => $login, 'password' => $password], $this->boolean('remember'))) {
                $isAuthenticated = true;
            }

            // Opção B: Tenta apenas com números (Limpo)
            // Útil se o banco guardou "12345678900" e o utilizador digitou "123.456.789-00"
            $cpfOnlyNumbers = preg_replace('/[^0-9]/', '', $login);
            if (!$isAuthenticated && Auth::attempt(['cpf' => $cpfOnlyNumbers, 'password' => $password], $this->boolean('remember'))) {
                $isAuthenticated = true;
            }

            // Opção C: Tenta formatado com pontos e traço
            // Útil se o banco guardou "123.456.789-00" e o utilizador digitou "12345678900"
            if (!$isAuthenticated && strlen($cpfOnlyNumbers) === 11) {
                $cpfFormatted = substr($cpfOnlyNumbers, 0, 3) . '.' .
                    substr($cpfOnlyNumbers, 3, 3) . '.' .
                    substr($cpfOnlyNumbers, 6, 3) . '-' .
                    substr($cpfOnlyNumbers, 9, 2);

                if (Auth::attempt(['cpf' => $cpfFormatted, 'password' => $password], $this->boolean('remember'))) {
                    $isAuthenticated = true;
                }
            }
        }

        if (!$isAuthenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
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
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('login')) . '|' . $this->ip());
    }
}
