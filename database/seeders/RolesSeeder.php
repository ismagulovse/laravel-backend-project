<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = $this->resolveActorId();

        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Full access to all system actions.'],
            ['name' => 'User', 'slug' => 'user', 'description' => 'Regular user with limited access.'],
            ['name' => 'Guest', 'slug' => 'guest', 'description' => 'Read-only guest access.'],
        ];

        foreach ($roles as $roleData) {
            $role = Role::query()->withTrashed()->where('slug', $roleData['slug'])->first();

            if ($role === null) {
                Role::query()->create([
                    'name' => $roleData['name'],
                    'slug' => $roleData['slug'],
                    'description' => $roleData['description'],
                    'created_by' => $actorId,
                ]);
                continue;
            }

            $role->name = $roleData['name'];
            $role->description = $roleData['description'];
            $role->created_by = $actorId;
            $role->save();

            if ($role->trashed()) {
                $role->restore();
                $role->deleted_by = null;
                $role->save();
            }
        }
    }

    private function resolveActorId(): int
    {
        $firstUser = User::query()->orderBy('id')->first();

        if ($firstUser !== null) {
            return (int) $firstUser->id;
        }

        $user = User::query()->create([
            'username' => 'system_admin',
            'email' => 'system_admin@example.com',
            'password' => Hash::make('Password1!'),
            'birthday' => Carbon::parse('2000-01-01'),
        ]);

        return (int) $user->id;
    }
}
