<?php

namespace App\Notifications\Concerns;

use App\Models\TravelRequest;
use Illuminate\Notifications\Messages\MailMessage;

trait BuildsTravelRequestMail
{
    protected function travelRequestMail(
        object $notifiable,
        TravelRequest $travelRequest,
        string $subject,
        string $headline,
        string $intro,
        string $actionText,
        string $actionUrl,
        string $tone = 'blue',
        ?string $comment = null,
        ?string $footnote = null,
    ): MailMessage {
        $travelRequest->loadMissing(['requester', 'unit', 'currentApprover']);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.travel-request-status', [
                'recipient' => $notifiable,
                'travelRequest' => $travelRequest,
                'headline' => $headline,
                'intro' => $intro,
                'actionText' => $actionText,
                'actionUrl' => $actionUrl,
                'tone' => $tone,
                'comment' => $comment,
                'footnote' => $footnote,
                'details' => $this->mailDetails($travelRequest),
            ]);
    }

    private function mailDetails(TravelRequest $travelRequest): array
    {
        return [
            'Request number' => $travelRequest->request_number,
            'Applicant' => $travelRequest->b_applicant_name ?: $travelRequest->requester?->name,
            'Unit' => $travelRequest->unit?->name,
            'Destination' => $travelRequest->b_destination,
            'Departure' => $travelRequest->b_departure_date?->format('d M Y'),
            'Return' => $travelRequest->b_return_date?->format('d M Y'),
            'Current status' => $travelRequest->statusLabel(),
        ];
    }
}
