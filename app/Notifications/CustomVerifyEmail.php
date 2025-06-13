<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // return (new MailMessage)
        //     ->line('The introduction to the notification.')
        //     ->action('Notification Action', url('/'))
        //     ->line('Thank you for using our application!');

        $verificationUrl = $this->verificationUrl($notifiable);

        // return (new MailMessage)
        //     ->subject('Verify Your Email Address')
        //     ->from(config('mail.from.address'), 'STWAG')
        //     ->greeting('Hello ' . $notifiable->name . '!')
        //     ->line('Thank you for registering STWAG! Please verify your email to complete the registration process.')
        //     ->action('Verify Email', $verificationUrl)
        //     ->line('If you did not create an account, no further action is required.')
        //     ->salutation('Regards, Your STWAG App Team');

        // No, the code you provided does not use your custom view (customMail). 
        // To use your custom view, you should return a Mailable or use the `view()` method on MailMessage, 
        // but MailMessage does not support custom Blade views directly. 
        // If you want to use a custom Blade view, you should create a Mailable class instead of a Notification, 
        // or use the `markdown()` method if your view is a Markdown mail template.

        // If you want to use a custom Markdown view, do this:
        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->from(config('mail.from.address'), 'STWAG')
            ->view('customMail', [
                'user' => $notifiable,
                'customSubject' => 'Verify Your Email Address',
                'customAction' => 'Verify Email',
                'customURL' => $verificationUrl,
                'customMessage' => 'Thank you for registering STWAG! Please verify your email to complete the registration process. 
            If you did not create an account, no further action is required.',
            ]);
    }


    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
