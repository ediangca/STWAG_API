<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Attributes\Log;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log as FacadesLog;

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
        FacadesLog::info('Sending custom mail', [
            'user_id' => $this->user->user_id ?? null,
            'email' => $this->user->email ?? null,
            'subject' => $this->customSubject,
            'message' => $this->customMessage,
        ]);

        return $this->subject($this->customSubject)
            ->from(config('mail.from.address'), 'STWAG')
            ->html(
            '<p>Dear ' . e($this->user->firstname . ' ' . substr($this->user->lastname, 0, 1)) . '.,</p>' .
            '<p>Greetings!</p>'.
            '<p>' . nl2br(e($this->customMessage)) . '</p>' .
            '<p>Best regards,<br><strong>STWAG Team</strong></p>');

        // return $this->subject($this->customSubject)
        //     ->view('customMail', [
        //         'user' => $this->user,
        //         'customSubject' => $this->customSubject,
        //         'customMessage' => $this->customMessage
        //     ]);

    }
}
