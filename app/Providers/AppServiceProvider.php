<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Support\Facades\Mail::extend('resend', function () {
            return new \App\Mail\Transports\ResendTransport(config('services.resend.key'));
        });

        \Illuminate\Support\Facades\Mail::extend('brevo', function () {
            return new \App\Mail\Transports\BrevoTransport(config('services.brevo.key'));
        });

        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Xác thực tài khoản Tuyết Nhàn')
                ->greeting('Xin chào, ' . $notifiable->fullname . '!')
                ->line('Cảm ơn bạn đã đăng ký tài khoản tại Đồ lễ Tuyết Nhàn. Để hoàn tất quá trình kích hoạt tài khoản, vui lòng nhấn vào nút bên dưới để xác thực email của bạn:')
                ->action('Xác thực Email', $url)
                ->line('Nếu bạn không yêu cầu đăng ký tài khoản này, vui lòng bỏ qua email này.')
                ->salutation('Trân trọng, Ban quản trị Đồ lễ Tuyết Nhàn');
        });
    }
}
