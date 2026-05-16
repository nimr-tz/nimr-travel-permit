<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestReturnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TravelRequest $travelRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tr         = $this->travelRequest;
        $url        = route('travel-requests.edit', $tr);
        $lastAction = $tr->approvalActions->last();

        return (new MailMessage)
            ->subject("Ombi la Safari Linahitaji Marekebisho — {$tr->request_number}")
            ->greeting("Habari {$notifiable->name},")
            ->line("Ombi lako la ruhusa ya kusafiri **LIMERUDISHWA** kwa marekebisho.")
            ->line("**Nambari:** {$tr->request_number}")
            ->line("**Marudio:** {$tr->b_destination}")
            ->when($lastAction?->comment, fn($m) =>
                $m->line("**Maelezo ya Marekebisho Yanayohitajika:** {$lastAction->comment}")
            )
            ->action('Hariri na Wasilisha Tena', $url)
            ->line('Tafadhali fanya marekebisho yanayotakiwa na uwasilishe ombi upya.')
            ->salutation('NIMR — Mfumo wa Ruhusa za Safari');
    }
}
