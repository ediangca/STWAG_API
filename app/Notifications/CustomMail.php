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
            ->html(
            '<p>Dear ' . e($this->user->name) . ',</p>' .
            '<p>Greetings!</p>' .
            '<p><strong>Subject:</strong> ' . e($this->customSubject) . '</p>' .
            '<p>' . nl2br(e($this->customMessage)) . '</p>' .
            '<p>Best regards,<br>STWAG Team</p>');
        
            // ->view('custom_user_mail.blade', [
            //     'user' => $this->user,
            //     'subject' => $this->customSubject,
            //     'message' => $this->customSubject
            // ]);
    }
}
