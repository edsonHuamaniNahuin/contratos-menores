<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
        public ?string $empresa,
        public ?string $telefono,
        public ?string $rubro,
        public ?string $landing,
        public ?string $origen,
    ) {}

    public function envelope(): Envelope
    {
        $asunto = $this->empresa
            ? "[Demo] {$this->empresa} quiere agendar una reunión"
            : "[Demo] {$this->nombre} quiere agendar una reunión";

        return new Envelope(
            subject: $asunto,
            replyTo: [$this->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-lead',
        );
    }
}
