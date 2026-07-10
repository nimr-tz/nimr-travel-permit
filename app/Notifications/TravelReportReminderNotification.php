<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use App\Notifications\Concerns\BuildsTravelRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelReportReminderNotification extends Notification implements ShouldQueue
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
            subject: "Travel report reminder - {$tr->request_number}",
            headline: 'Travel report required',
            intro: 'This approved trip has ended, but the travel report has not been uploaded yet. Please upload the report from the travel request page.',
            actionText: 'Upload travel report',
            actionUrl: route('travel-requests.show', $tr),
            tone: 'amber',
            footnote: 'Reminders continue every 3 days until the travel report is uploaded.',
        );
    }
}
