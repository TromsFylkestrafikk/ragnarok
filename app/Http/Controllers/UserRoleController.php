<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRoleController extends Controller
{
    /**
     * Get all users and their role.
     */
    protected function getAllUsers()
    {
        $users = [];
        $userData = User::withOnly('roles:name')->select('id', 'name', 'email')->get();
        $userData->each(function ($user) use (&$users) {
            $users[] = [
                'id' => $user->id,
                'name' => $user->name,
                'mail' => $user->email,
                'role' => $user->roles->value('name'),
            ];
        });
        return $users;
    }

    /**
     * Get data for the 'User role' table, assuming thet each user has only one role.
     */
    public function getUsersWithRoles()
    {
        $users = $this->getAllUsers();
        return response([
            'users' => $users,
            'userId' => auth()->user()->id,
            'admins' => collect($users)->where('role', 'admin')->count(),
            'canEditUsers' => auth()->user()->can('edit users'),
        ]);
    }

    /**
     * Generate permissions table.
     */
    public function getRolesAndPermissions()
    {
        $table = [];
        $roles = Role::select('id', 'name')->orderBy('id', 'desc')->get();
        $permissions = Permission::withOnly('roles:name')->select('id', 'name')->orderBy('id')->get();
        $permissions->each(function ($permission) use (&$table, $roles) {
            $row = [$permission->name];
            $permissionRoles = $permission->roles->pluck('name')->toArray();
            $roles->each(function ($role) use (&$row, $permissionRoles) {
                $row[] = in_array($role->name, $permissionRoles);
            });
            $table[] = $row;
        });
        return response([
            'roles' => $roles,
            'permissions' => $table,
        ]);
    }

    /**
     * Assign a role to the selected user.
     */
    public function setUserRole($memberId, $memberRole)
    {
        if (!auth()->user()->can('edit users')) {
            return response([
                'success' => false,
            ]);
        }
        // Clear all existing roles for the selected user and assign the tnew role.
        $member = User::where('id', $memberId)->first();
        $member->syncRoles([]);
        $member->assignRole($memberRole);

        // Return re-calculated number of users with admin role.
        $users = $this->getAllUsers();
        return response([
            'admins' => collect($users)->where('role', 'admin')->count(),
            'canEditUsers' => auth()->user()->id !== intval($memberId, 10),
            'success' => true,
        ]);
    }
}
