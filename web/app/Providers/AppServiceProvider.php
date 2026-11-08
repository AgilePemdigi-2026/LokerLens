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
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Pemberitahuan Atur Ulang Kata Sandi — LokerLens')
                ->greeting('Halo, ' . ($notifiable->name ?? 'Pengguna LokerLens') . '!')
                ->line('Anda menerima email ini karena kami menerima permintaan atur ulang kata sandi untuk akun Anda.')
                ->action('Atur Ulang Kata Sandi', $url)
                ->line('Tautan atur ulang kata sandi ini akan kedaluwarsa dalam 60 menit.')
                ->line('Jika Anda tidak meminta atur ulang kata sandi, abaikan email ini.')
                ->salutation('Salam hormat,' . "\n" . 'Tim Pengawas Ketenagakerjaan LokerLens');
        });
    }
}
