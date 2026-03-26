<?php

namespace App\Http\Requests\Auth;

use App\Support\MiningPlatform;
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
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

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            $attempts = RateLimiter::attempts($this->throttleKey());

            if ($attempts >= 3) {
                MiningPlatform::notifyAdminsOfCriticalAlert(
                    'Repeated failed login attempts detected',
                    'A login identifier has failed authentication multiple times.',
                    'Attempts: '.$attempts,
                    'Review recent activity and confirm the admin account is protected with 2FA.',
                    'Login identifier',
                    Str::lower((string) $this->input('email')),
                    [
                        'email' => Str::lower((string) $this->input('email')),
                        'ip' => $this->ip(),
                        'attempts' => $attempts,
                    ],
                );
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
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

        MiningPlatform::notifyAdminsOfCriticalAlert(
            'Login lockout triggered',
            'A login identifier has been rate limited after too many failed attempts.',
            'Throttle window: '.$seconds.' seconds',
            'Investigate the login source if this repeats.',
            'Login identifier',
            Str::lower((string) $this->input('email')),
            [
                'email' => Str::lower((string) $this->input('email')),
                'ip' => $this->ip(),
                'seconds' => $seconds,
            ],
        );

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
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
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
