<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);

        Permission::create(['name' => 'read sources']);
        Permission::create(['name' => 'import sources']);
        Permission::create(['name' => 'edit sources']);

        // @var Role
        $adminRole = Role::create(['name' => 'admin']);
        // @var Role
        $maintainerRole = Role::create(['name' => 'maintainer']);
        // @var Role
        $readerRole = Role::create(['name' => 'reader']);

        $adminRole->givePermissionTo([
            'create users',
            'delete users',
            'edit users',
            'read sources',
            'import sources',
            'edit sources',
        ]);

        $maintainerRole->givePermissionTo([
            'read sources',
            'import sources',
            'edit sources',
        ]);

        $readerRole->givePermissionTo('read sources');
    }
}
