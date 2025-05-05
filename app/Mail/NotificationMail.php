<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable {
    use Queueable, SerializesModels;

    protected $htmlTitle;
    protected $header;
    protected $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($htmlTitle, $header, $message, $email) {
        $this->htmlTitle = $htmlTitle;
        $this->header = $header;
        $this->message = $message;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->from("noreply@FinessApp.app")
                    ->subject($this->htmlTitle)
                    ->view('emails.notificationMail', [
                        'html_title' => $this->htmlTitle,
                        'header' => $this->header,
                        'body' => $this->message,
                        'email' => $this->email,
                    ]);
    }
}
