<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * System administration used to be one of the values of users.role, so an
     * administrator could hold no other post: the ICT Manager who also runs
     * the system had to be either "Manager" (and lose /users) or "System
     * Administrator" (and drop out of the approval chain as a plain staff
     * member). It is a permission, not a post, so it moves to its own flag and
     * users.role goes back to describing only where someone sits in the
     * organogram.
     *
     * Existing administrators become 'staff' with the flag set — the approval
     * chain already treated the system_admin role exactly like staff, so
     * nobody's reporting line changes. An administrator who actually holds a
     * post can now be given it from the user form.
     *
     * 'system_admin' stays an allowed value in the users.role constraint:
     * removing it means another per-driver table rebuild, and nothing assigns
     * it any more.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system_admin')->default(false)->after('role');
        });

        DB::table('users')
            ->where('role', 'system_admin')
            ->update(['is_system_admin' => true, 'role' => 'staff']);
    }

    /**
     * Lossy: an administrator who has since been given a post other than
     * 'staff' keeps that post and loses administrator access, because the old
     * schema cannot represent both.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('is_system_admin', true)
            ->where('role', 'staff')
            ->update(['role' => 'system_admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_system_admin');
        });
    }
};
