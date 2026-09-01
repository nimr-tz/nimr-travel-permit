<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use App\Notifications\Concerns\BuildsTravelRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the person named in Section G that duties are being handed to them.
 * Until this existed the handover officer was never told at all — their name
 * sat on the permit and the first they heard of it was the traveller leaving.
 *
 * Sent twice on purpose: once when the permit is submitted, so they can object
 * while the approval chain is still running, and once when it is approved, so
 * they know the trip is actually happening.
 */
class TravelRequestHandoverNotification extends Notification implements ShouldQueue
{
    use Queueable, BuildsTravelRequestMail;

    public int $tries = 5;

    public const STAGE_SUBMITTED = 'submitted';
    public const STAGE_APPROVED = 'approved';

    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    public function __construct(
        public TravelRequest $travelRequest,
        public string $stage = self::STAGE_SUBMITTED,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tr = $this->travelRequest;
        $traveller = $tr->b_applicant_name ?? $tr->requester?->name ?? 'A colleague';
        $approved = $this->stage === self::STAGE_APPROVED;

        $dates = $tr->b_departure_date && $tr->b_return_date
            ? ' from ' . $tr->b_departure_date->format('j M Y') . ' to ' . $tr->b_return_date->format('j M Y')
            : '';

        return $this->travelRequestMail(
            notifiable: $notifiable,
            travelRequest: $tr,
            subject: $approved
                ? "Handover confirmed - {$tr->request_number}"
                : "You have been named as handover officer - {$tr->request_number}",
            headline: $approved
                ? 'A handover to you is now confirmed'
                : 'You have been named as a handover officer',
            intro: $approved
                ? "{$traveller}'s travel has been approved and you are named as the handover officer{$dates}. Their duties pass to you for that period."
                : "{$traveller} has named you as the handover officer on a travel permit{$dates}. The request still has to clear its approval chain — raise any objection with them before it does.",
            actionText: 'View request',
            actionUrl: route('travel-requests.show', $tr),
        );
    }
}
