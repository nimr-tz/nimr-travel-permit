<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    public function __construct(public TravelRequest $travelRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tr      = $this->travelRequest;
        $url     = route('travel-requests.show', $tr);
        $lastAction = $tr->approvalActions->last();

        return (new MailMessage)
            ->subject("Ombi la Safari Limekataliwa — {$tr->request_number}")
            ->greeting("Habari {$notifiable->name},")
            ->line("Ombi lako la ruhusa ya kusafiri **LIMEKATALIWA**.")
            ->line("**Nambari:** {$tr->request_number}")
            ->line("**Marudio:** {$tr->b_destination}")
            ->when($lastAction?->comment, fn($m) =>
                $m->line("**Sababu:** {$lastAction->comment}")
            )
            ->action('Angalia Ombi', $url)
            ->line('Unaweza kuwasiliana na Idara ya Rasilimali Watu kwa maelezo zaidi.')
            ->salutation('NIMR — Mfumo wa Ruhusa za Safari');
    }
}
