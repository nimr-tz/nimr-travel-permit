<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestApprovedNotification extends Notification implements ShouldQueue
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
        $tr  = $this->travelRequest;
        $url = route('travel-requests.show', $tr);

        return (new MailMessage)
            ->subject("Ombi la Safari Limeidhinishwa — {$tr->request_number}")
            ->greeting("Habari {$notifiable->name},")
            ->line("Ombi lako la ruhusa ya kusafiri **LIMEIDHINISHWA** na wataalam wote.")
            ->line("**Nambari:** {$tr->request_number}")
            ->line("**Marudio:** {$tr->b_destination}")
            ->line("**Tarehe ya Kuondoka:** " . $tr->b_departure_date?->format('d M Y'))
            ->line("**Tarehe ya Kurudi:** " . $tr->b_return_date?->format('d M Y'))
            ->action('Angalia na Chapisha Fomu', $url)
            ->line('Unaweza sasa kupakua nakala ya fomu iliyoidhinishwa kwa safari yako.')
            ->salutation('NIMR — Mfumo wa Ruhusa za Safari');
    }
}
