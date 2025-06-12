<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $customMessage;
    public $customSubject;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $customSubject, $customMessage)
    {
        $this->user = $user;
        $this->customSubject = $customSubject;
        $this->customMessage = $customMessage;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->customSubject)
            ->from(config('mail.from.address'), 'STWAG')
            ->view('custom_user_mail.blade', [
                'user' => $this->user,
                'subject' => $this->customSubject,
                'message' => $this->customSubject
            ]);
    }
}
