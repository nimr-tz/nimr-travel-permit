<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use App\Notifications\Concerns\BuildsTravelRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestSubmittedNotification extends Notification implements ShouldQueue
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
            subject: "Travel request awaiting your approval - {$tr->request_number}",
            headline: 'A travel request needs your review',
            intro: 'A staff travel permit has reached your approval desk. Please review the details and record your decision in the system.',
            actionText: 'Review request',
            actionUrl: route('travel-requests.show', $tr),
        );
    }
}
