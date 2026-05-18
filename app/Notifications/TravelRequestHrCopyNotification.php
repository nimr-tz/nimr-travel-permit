<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestHrCopyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    public function __construct(
        public TravelRequest $travelRequest,
        public string $event = 'submitted' // 'submitted' | 'approved' | 'rejected' | 'returned'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tr  = $this->travelRequest;
        $url = route('travel-requests.show', $tr);

        $subjects = [
            'submitted' => "Nakala — Ombi Jipya la Safari: {$tr->request_number}",
            'approved'  => "Nakala — Ombi Limeidhinishwa: {$tr->request_number}",
            'rejected'  => "Nakala — Ombi Limekataliwa: {$tr->request_number}",
            'returned'  => "Nakala — Ombi Limerudishwa: {$tr->request_number}",
        ];

        $intros = [
            'submitted' => 'Ombi jipya la ruhusa ya kusafiri limewasilishwa. Nakala hii ni kwa kumbukumbu ya Idara ya Rasilimali Watu.',
            'approved'  => 'Ombi la ruhusa ya kusafiri **LIMEIDHINISHWA** na wasimamizi wote. Nakala hii ni kwa kumbukumbu yako.',
            'rejected'  => 'Ombi la ruhusa ya kusafiri **LIMEKATALIWA**. Nakala hii ni kwa kumbukumbu yako.',
            'returned'  => 'Ombi la ruhusa ya kusafiri limerudishwa kwa marekebisho. Nakala hii ni kwa kumbukumbu yako.',
        ];

        return (new MailMessage)
            ->subject($subjects[$this->event] ?? $subjects['submitted'])
            ->greeting("Habari {$notifiable->name},")
            ->line($intros[$this->event] ?? $intros['submitted'])
            ->line("**Mwombaji:** {$tr->b_applicant_name}")
            ->line("**Nambari ya Ombi:** {$tr->request_number}")
            ->line("**Kitengo:** " . ($tr->unit?->name ?? '—'))
            ->line("**Marudio:** {$tr->b_destination}")
            ->line("**Tarehe ya Kuondoka:** " . $tr->b_departure_date?->format('d M Y'))
            ->line("**Tarehe ya Kurudi:** " . $tr->b_return_date?->format('d M Y'))
            ->action('Angalia Ombi', $url)
            ->line('Barua pepe hii ni nakala ya taarifa tu. Hakuna hatua inayohitajika kutoka kwa Idara ya Rasilimali Watu.')
            ->salutation('NIMR — Mfumo wa Ruhusa za Safari');
    }
}
