<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $type,     // 'registration' or 'forgot_password'
        public string $userName = ''
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->type === 'registration'
            ? 'Verify Your ScholarHub Account'
            : 'Reset Your ScholarHub Password';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp');
    }

    public function attachments(): array { return []; }
}
