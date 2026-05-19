<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use App\Notifications\Concerns\BuildsTravelRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestReminderNotification extends Notification implements ShouldQueue
{
    use Queueable, BuildsTravelRequestMail;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public TravelRequest $travelRequest, public int $daysWaiting) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->travelRequestMail(
            notifiable:  $notifiable,
            travelRequest: $this->travelRequest,
            subject:     "Action Required: Travel request pending for {$this->daysWaiting} day(s)",
            headline:    'Pending Approval Reminder',
            intro:       "A travel request has been waiting for your approval for {$this->daysWaiting} day(s). Please review and take action.",
            tone:        'warning',
            actionUrl:   route('travel-requests.show', $this->travelRequest->id),
            actionText:  'Review Request',
        );
    }
}
