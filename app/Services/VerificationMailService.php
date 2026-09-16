<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

/**
 * Sends the email verification link synchronously and turns a mail failure
 * into a message the user can act on, instead of a 500 page.
 *
 * The NIMR mail server rejects addresses it has no mailbox for with a 550
 * ("User unknown in virtual mailbox table"). That is almost always a typo or
 * a mailbox ICT has not created yet, so it is the user's problem to fix, not
 * a system fault worth alerting on.
 */
class VerificationMailService
{
    /** SMTP codes meaning the mailbox itself was refused. */
    private const RECIPIENT_REJECTED_CODES = [550, 551, 553];

    /**
     * @throws ValidationException on the `email` field when the link cannot be delivered
     */
    public function send(User $user): void
    {
        try {
            $user->sendEmailVerificationNotification();
        } catch (TransportExceptionInterface $e) {
            if ($this->recipientRejected($e)) {
                logger()->warning('Verification email rejected by mail server', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'error'   => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'email' => __('auth.email_undeliverable', ['email' => $user->email]),
                ]);
            }

            // Mail server down or misconfigured: a real fault, keep alerting.
            report($e);

            throw ValidationException::withMessages([
                'email' => __('auth.email_send_failed'),
            ]);
        }
    }

    private function recipientRejected(TransportExceptionInterface $e): bool
    {
        return $e instanceof UnexpectedResponseException
            && in_array($e->getCode(), self::RECIPIENT_REJECTED_CODES, true);
    }
}
