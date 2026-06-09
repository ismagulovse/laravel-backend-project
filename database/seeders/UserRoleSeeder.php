<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureBaseUsers();

        $actorId = $this->resolveActorId();
        $adminRole = Role::query()->where('slug', 'admin')->first();
        $userRole = Role::query()->where('slug', 'user')->first();

        if ($adminRole === null || $userRole === null) {
            return;
        }

        $users = User::query()->orderBy('id')->get();

        if ($users->isEmpty()) {
            return;
        }

        $firstUser = $users->first();
        if ($firstUser !== null) {
            $this->attachOrRestore((int) $firstUser->id, (int) $adminRole->id, $actorId);
        }

        foreach ($users->slice(1) as $user) {
            $this->attachOrRestore((int) $user->id, (int) $userRole->id, $actorId);
        }
    }

    private function attachOrRestore(int $userId, int $roleId, int $actorId): void
    {
        $link = UserRole::query()
            ->withTrashed()
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->first();

        if ($link === null) {
            UserRole::query()->create([
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_by' => $actorId,
            ]);
            return;
        }

        if ($link->trashed()) {
            $link->restore();
            $link->deleted_by = null;
            $link->save();
        }
    }

    private function ensureBaseUsers(): void
    {
        // Создаём базовых пользователей только если таблица пустая.
        if (User::query()->exists()) {
            return;
        }

        User::query()->create([
            'username' => 'admin_user',
            'email' => 'admin_user@example.com',
            'password' => Hash::make('Password1!'),
            'birthday' => Carbon::parse('1995-01-01'),
        ]);

        User::query()->create([
            'username' => 'simple_user',
            'email' => 'simple_user@example.com',
            'password' => Hash::make('Password1!'),
            'birthday' => Carbon::parse('1998-01-01'),
        ]);

        User::query()->create([
            'username' => 'guest_user',
            'email' => 'guest_user@example.com',
            'password' => Hash::make('Password1!'),
            'birthday' => Carbon::parse('2001-01-01'),
        ]);
    }

    private function resolveActorId(): int
    {
        $firstUser = User::query()->orderBy('id')->first();

        return (int) ($firstUser->id ?? 0);
    }
}
