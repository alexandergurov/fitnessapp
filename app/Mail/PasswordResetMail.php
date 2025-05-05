<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable {
    use Queueable, SerializesModels;

    protected $userName;
    protected $resetCode;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($code, $name, $email) {
        $this->userName = $name;
        $this->resetCode = $code;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->from("noreply@FinessApp.app")
                    ->view('emails.resetPassword', [
                        'resetCode' => $this->resetCode,
                        'name' => $this->userName,
                        'email' => $this->email,
                    ]);
    }
}
