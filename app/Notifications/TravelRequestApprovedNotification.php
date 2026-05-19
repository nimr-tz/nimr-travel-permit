<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use App\Notifications\Concerns\BuildsTravelRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable, BuildsTravelRequestMail;

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
        $tr = $this->travelRequest;

        return $this->travelRequestMail(
            notifiable: $notifiable,
            travelRequest: $tr,
            subject: "Travel request approved - {$tr->request_number}",
            headline: 'Your travel permit has been approved',
            intro: 'The final approver has approved your internal travel permit. You can now view or print the approved request.',
            actionText: 'View approved permit',
            actionUrl: route('travel-requests.show', $tr),
            tone: 'green',
            footnote: 'Please keep a copy of the approved permit for travel and administrative records.',
        );
    }
}
