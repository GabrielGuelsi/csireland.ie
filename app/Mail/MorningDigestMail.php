<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MorningDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $agent,
        public array $summary,         // [ status => ['count' => int] ]
        public array $birthdays,       // Student[]
        public array $examsToday,      // Student[]
        public int   $pendingMessages  // count of pending scheduled messages
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CI Ireland — Your morning student summary',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.morning_digest',
        );
    }
}
