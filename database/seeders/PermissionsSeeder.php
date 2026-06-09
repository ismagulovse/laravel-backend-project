<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = $this->resolveActorId();

        $actions = ['get-list', 'read', 'create', 'update', 'delete', 'restore'];
        $entities = ['user', 'role', 'permission'];

        // Разрешения для просмотра истории изменений — только для администраторов.
        $storyPermissions = ['get-story-user', 'get-story-role', 'get-story-permission'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $slug = $action . '-' . $entity;
                $name = strtoupper($slug);
                $description = 'Permission for action "' . $action . '" on "' . $entity . '".';

                $permission = Permission::query()->withTrashed()->where('slug', $slug)->first();

                if ($permission === null) {
                    Permission::query()->create([
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'created_by' => $actorId,
                    ]);
                    continue;
                }

                $permission->name = $name;
                $permission->description = $description;
                $permission->created_by = $actorId;
                $permission->save();

                if ($permission->trashed()) {
                    $permission->restore();
                    $permission->deleted_by = null;
                    $permission->save();
                }
            }
        }

        foreach ($storyPermissions as $slug) {
            $name = strtoupper($slug);
            $entity = explode('-', $slug)[2];
            $description = 'Permission to view change history of "' . $entity . '".';

            $permission = Permission::query()->withTrashed()->where('slug', $slug)->first();

            if ($permission === null) {
                Permission::query()->create([
                    'name'        => $name,
                    'slug'        => $slug,
                    'description' => $description,
                    'created_by'  => $actorId,
                ]);
                continue;
            }

            $permission->name = $name;
            $permission->description = $description;
            $permission->save();

            if ($permission->trashed()) {
                $permission->restore();
                $permission->deleted_by = null;
                $permission->save();
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
