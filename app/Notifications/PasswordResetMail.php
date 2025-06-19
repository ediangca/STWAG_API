<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log as FacadesLog;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $user;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {

        FacadesLog::info('Sending forgot password mail', [
            'user_id' => $this->user->user_id ?? null,
            'email' => $this->user->email ?? null,
        ]);
        
        return $this->subject('Password Reset Request')
            ->view('customMail', [
                'user' => $this->user,
                'customSubject' => 'Password Reset Request',
                'customAction' => 'Reset Password',
                'customURL' => env('APP_URL') . '/auth/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email),
                'customMessage' => 'We received a request to reset your password for your STWAG account. Click the button below to reset your password. If you did not request a password reset, please ignore this email.'
            ]);
    }
}
