<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $helpers = app_path('helpers.php');
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset Your ZagChain Password')
                ->greeting('Hello '.$notifiable->name.',')
                ->line('We received a request to reset your ZagChain password.')
                ->action('Reset ZagChain Password', $resetUrl)
                ->line('This password reset link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
                ->line('If you did not request a password reset, you can safely ignore this email.');
        });
    }
}
