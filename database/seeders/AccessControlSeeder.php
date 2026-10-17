<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::ALL as $group => $permissions) {
            foreach ($permissions as $slug => $name) {
                Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'group' => $group]);
            }
        }

        foreach (Permissions::ROLES as $slug => $definition) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $definition['name']]);
            // Only set default permissions on first creation; admins may customise later.
            if ($role->wasRecentlyCreated && $definition['permissions'] !== '*') {
                $role->permissions()->sync(Permission::whereIn('slug', $definition['permissions'])->pluck('id'));
            }
        }
    }
}
