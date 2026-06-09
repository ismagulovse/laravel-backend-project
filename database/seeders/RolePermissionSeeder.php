<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = $this->resolveActorId();

        $adminRole = Role::query()->where('slug', 'admin')->first();
        $userRole = Role::query()->where('slug', 'user')->first();
        $guestRole = Role::query()->where('slug', 'guest')->first();

        if ($adminRole === null || $userRole === null || $guestRole === null) {
            return;
        }

        $allPermissionIds = Permission::query()->pluck('id')->all();
        foreach ($allPermissionIds as $permissionId) {
            $this->attachOrRestore((int) $adminRole->id, (int) $permissionId, $actorId);
        }

        $userPermissionSlugs = ['get-list-user', 'read-user', 'update-user'];
        $userPermissionIds = Permission::query()
            ->whereIn('slug', $userPermissionSlugs)
            ->pluck('id')
            ->all();

        foreach ($userPermissionIds as $permissionId) {
            $this->attachOrRestore((int) $userRole->id, (int) $permissionId, $actorId);
        }

        $guestPermissionId = Permission::query()
            ->where('slug', 'get-list-user')
            ->value('id');

        if ($guestPermissionId !== null) {
            $this->attachOrRestore((int) $guestRole->id, (int) $guestPermissionId, $actorId);
        }
    }

    private function attachOrRestore(int $roleId, int $permissionId, int $actorId): void
    {
        $link = PermissionRole::query()
            ->withTrashed()
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->first();

        if ($link === null) {
            PermissionRole::query()->create([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
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
