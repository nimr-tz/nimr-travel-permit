<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use App\Notifications\Concerns\BuildsTravelRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestRejectedNotification extends Notification implements ShouldQueue
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
            subject: "Travel request rejected - {$tr->request_number}",
            headline: 'Your travel request was not approved',
            intro: 'An approver has rejected this travel permit request. Review the request record and comments for the reason.',
            actionText: 'View request',
            actionUrl: route('travel-requests.show', $tr),
            tone: 'red',
            comment: $lastAction?->comment,
            footnote: 'For clarification, contact the approver or HR using the official internal channels.',
        );
    }
}
