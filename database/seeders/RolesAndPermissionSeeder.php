<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'admin'])->givePermissionTo([
            'create users',
            'delete users',
            'edit users',
            'read sources',
            'import sources',
            'edit sources',
        ]);

        Role::create(['name' => 'maintainer'])->givePermissionTo([
            'read sources',
            'import sources',
            'edit sources',
        ]);

        Role::create(['name' => 'reader'])->givePermissionTo('read sources');
    }
}
