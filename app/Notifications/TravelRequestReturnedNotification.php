<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use App\Notifications\Concerns\BuildsTravelRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestReturnedNotification extends Notification implements ShouldQueue
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
        $lastAction = $tr->approvalActions->last();

        return $this->travelRequestMail(
            notifiable: $notifiable,
            travelRequest: $tr,
            subject: "Travel request returned for revision - {$tr->request_number}",
            headline: 'Your request needs revision',
            intro: 'An approver has returned this travel permit for correction. Update the request and resubmit it when ready.',
            actionText: 'Edit and resubmit',
            actionUrl: route('travel-requests.edit', $tr),
            tone: 'amber',
            comment: $lastAction?->comment,
        );
    }
}
