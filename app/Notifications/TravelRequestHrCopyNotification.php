<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use App\Notifications\Concerns\BuildsTravelRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestHrCopyNotification extends Notification implements ShouldQueue
{
    use Queueable, BuildsTravelRequestMail;

    public int $tries = 5;

    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    public function __construct(
        public TravelRequest $travelRequest,
        public string $event = 'submitted'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tr = $this->travelRequest;
        $event = $this->eventCopy()[$this->event] ?? $this->eventCopy()['submitted'];

        return $this->travelRequestMail(
            notifiable: $notifiable,
            travelRequest: $tr,
            subject: "{$event['subject']} - {$tr->request_number}",
            headline: $event['headline'],
            intro: $event['intro'],
            actionText: 'Open request record',
            actionUrl: route('travel-requests.show', $tr),
            tone: $event['tone'],
            footnote: 'This is an HR records copy only. No approval action is required from HR.',
        );
    }

    private function eventCopy(): array
    {
        return [
            'submitted' => [
                'subject' => 'HR copy: travel request submitted',
                'headline' => 'Travel request submitted',
                'intro' => 'A travel permit has been submitted and routed to the assigned approver. This copy is for HR visibility and records.',
                'tone' => 'blue',
            ],
            'approved' => [
                'subject' => 'HR copy: travel request approved',
                'headline' => 'Travel request approved',
                'intro' => 'The assigned approval chain has completed this travel permit. This copy is for HR records.',
                'tone' => 'green',
            ],
            'rejected' => [
                'subject' => 'HR copy: travel request rejected',
                'headline' => 'Travel request rejected',
                'intro' => 'An approver has rejected this travel permit. This copy is for HR records.',
                'tone' => 'red',
            ],
            'returned' => [
                'subject' => 'HR copy: travel request returned',
                'headline' => 'Travel request returned for revision',
                'intro' => 'An approver has returned this travel permit for correction. This copy is for HR visibility.',
                'tone' => 'amber',
            ],
        ];
    }
}
