<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Forcibly ends every signed-in session belonging to a user.
 *
 * Used when an account is deactivated: clearing is_active alone leaves any
 * open session and any "remember me" cookie working.
 */
class SessionRevocationService
{
    public function revokeAllFor(User $user): void
    {
        // Invalidates the remember-me cookie, which survives session deletion.
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (Config::get('session.driver') !== 'database') {
            return;
        }

        DB::connection(Config::get('session.connection'))
            ->table(Config::get('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
