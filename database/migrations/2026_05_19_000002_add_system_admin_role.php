<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $roles = [
        'staff',
        'head',
        'manager',
        'director',
        'centre_manager',
        'director_general',
        'hr',
        'system_admin',
    ];

    public function up(): void
    {
        $this->applyRoles($this->roles);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'system_admin')->update(['role' => 'staff']);

        $this->applyRoles(array_values(array_filter($this->roles, fn ($role) => $role !== 'system_admin')));
    }

    /**
     * Constrain users.role to the given set, per database engine. Dispatch is
     * explicit: the SQLite table rebuild below is not valid SQL anywhere else,
     * so an unrecognised driver must fail loudly rather than run it.
     */
    private function applyRoles(array $roles): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => $this->applyRolesMysql($roles),
            'pgsql' => $this->applyRolesPostgres($roles),
            'sqlite' => $this->rebuildUsersTable($roles),
            default => throw new RuntimeException(
                "Cannot constrain users.role on unsupported database driver [{$driver}]."
            ),
        };
    }

    private function applyRolesMysql(array $roles): void
    {
        $roleList = implode("','", $roles);

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('{$roleList}') NOT NULL DEFAULT 'staff'");
    }

    private function applyRolesPostgres(array $roles): void
    {
        $roleList = implode("','", $roles);

        // Laravel renders an enum column as varchar plus a CHECK constraint.
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text IN ('{$roleList}'))");
    }

    private function rebuildUsersTable(array $roles): void
    {
        $roleList = implode('\', \'', $roles);

        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::statement("
            CREATE TABLE \"users_new\" (
                \"id\" integer primary key autoincrement not null,
                \"unit_id\" integer,
                \"name\" varchar not null,
                \"email\" varchar not null,
                \"phone\" varchar,
                \"job_title\" varchar,
                \"avatar_path\" varchar,
                \"role\" varchar check (\"role\" in ('{$roleList}')) not null default 'staff',
                \"supervisor_id\" integer,
                \"is_active\" tinyint(1) not null default '1',
                \"email_verified_at\" datetime,
                \"password\" varchar not null,
                \"remember_token\" varchar,
                \"created_at\" datetime,
                \"updated_at\" datetime,
                foreign key(\"unit_id\") references \"units\"(\"id\") on delete set null,
                foreign key(\"supervisor_id\") references \"users\"(\"id\") on delete set null
            )
        ");

        DB::statement('
            INSERT INTO "users_new" (
                "id", "unit_id", "name", "email", "phone", "job_title",
                "avatar_path", "role", "supervisor_id", "is_active", "email_verified_at",
                "password", "remember_token", "created_at", "updated_at"
            )
            SELECT
                "id", "unit_id", "name", "email", "phone", "job_title",
                "avatar_path", "role", "supervisor_id", "is_active", "email_verified_at",
                "password", "remember_token", "created_at", "updated_at"
            FROM "users"
        ');

        DB::statement('DROP TABLE "users"');
        DB::statement('ALTER TABLE "users_new" RENAME TO "users"');
        DB::statement('CREATE UNIQUE INDEX "users_email_unique" on "users" ("email")');

        DB::statement('PRAGMA foreign_keys = ON;');
    }
};
