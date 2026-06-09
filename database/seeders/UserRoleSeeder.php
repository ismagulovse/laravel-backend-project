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

        // Явно сопоставляем username → slug роли.
        $assignments = [
            'Adminuser'  => 'admin',
            'Simpleuser' => 'user',
            'Guestuser'  => 'guest',
        ];

        foreach ($assignments as $username => $roleSlug) {
            $user = User::query()->where('username', $username)->first();
            $role = Role::query()->where('slug', $roleSlug)->first();

            if ($user === null || $role === null) {
                continue;
            }

            $this->attachOrRestore((int) $user->id, (int) $role->id, $actorId);
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
        // Создаём каждого базового пользователя, если его ещё нет.
        // firstOrCreate ищет по email; если не найдёт — создаст с указанными полями.
        $baseUsers = [
            ['username' => 'Adminuser',  'email' => 'admin_user@example.com',  'birthday' => '1995-01-01'],
            ['username' => 'Simpleuser', 'email' => 'simple_user@example.com', 'birthday' => '1998-01-01'],
            ['username' => 'Guestuser',  'email' => 'guest_user@example.com',  'birthday' => '2001-01-01'],
        ];

        foreach ($baseUsers as $data) {
            User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'username' => $data['username'],
                    'password' => Hash::make('Password1!'),
                    'birthday' => Carbon::parse($data['birthday']),
                ],
            );
        }
    }

    private function resolveActorId(): int
    {
        $firstUser = User::query()->orderBy('id')->first();

        return (int) ($firstUser->id ?? 0);
    }
}
