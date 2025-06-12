<?php

namespace App\Services;

use Illuminate\Mail\Mailables;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;


class UserVerifiedMail extends Mailable
{

    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build($subject, $message)
    {
        return $this
            ->subject($subject)
            ->from(config('mail.from.address'), 'STWAG')
            ->greeting('Hello ' . strtoupper($this->user->firstname . ' ' . strtoupper(substr($this->user->lastname, 0, 1)) . '!'))
            ->line($message)
            ->salutation('Regards, Your STWAG App Team');
            
    }
}
