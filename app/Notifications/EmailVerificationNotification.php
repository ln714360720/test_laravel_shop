<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;//说明使命队列处理,并实现了ShouldQueue接口,所以要开启一个队列任务

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        
        $token=Str::random(16);
        Cache::put('email_verification_'.$notifiable->email, $token, 30);
        $url=route('email_verification.verify',['email'=>$notifiable->email,'token'=>$token]);
        return (new MailMessage)
                    ->greeting($notifiable->name.'您好:')
                    ->subject('注册成功,请验证你的邮箱')
                    ->line('请点击下方的链接验证您的邮箱')
                    ->action('验证', $url)
                    ->line('感谢您使用我们应用!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
