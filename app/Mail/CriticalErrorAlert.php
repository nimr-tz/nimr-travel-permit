<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CriticalErrorAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $context)
    {
    }

    public function envelope(): Envelope
    {
        $exceptionClass = class_basename($this->context['exception']['class'] ?? 'Exception');
        $routeName = $this->context['request']['route_name'] ?? $this->context['request']['url'] ?? 'unknown route';

        return new Envelope(
            subject: sprintf('[%s] Critical error: %s on %s', $this->context['app_name'] ?? 'AJSC', $exceptionClass, $routeName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.critical-error-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
