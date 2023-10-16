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

        Permission::create(['name' => 'read sinks']);
        Permission::create(['name' => 'read chunks']);
        Permission::create(['name' => 'fetch chunks']);
        Permission::create(['name' => 'import chunks']);
        Permission::create(['name' => 'deleteFetched chunks']);
        Permission::create(['name' => 'deleteImported chunks']);
        Permission::create(['name' => 'delete batches']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'admin'])->givePermissionTo([
            'create users',
            'delete users',
            'edit users',
            'read sinks',
            'read chunks',
            'fetch chunks',
            'import chunks',
            'deleteFetched chunks',
            'deleteImported chunks',
            'delete batches',
        ]);

        Role::create(['name' => 'maintainer'])->givePermissionTo([
            'read sinks',
            'read chunks',
            'fetch chunks',
            'import chunks',
            'deleteFetched chunks',
            'deleteImported chunks',
            'delete batches',
        ]);

        Role::create(['name' => 'reader'])->givePermissionTo([
            'read sinks',
            'read chunks',
        ]);
    }
}
