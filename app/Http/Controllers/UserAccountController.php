<?php

namespace App\Http\Controllers;

use App\Mail\UserAccountDeleted;
use App\Mail\UserAccountInfo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserAccountController extends Controller
{
    /**
     * Create a new user account.
     */
    public function addUserAccount(Request $request)
    {
        if (!auth()->user()->can('create users')) {
            return redirect()->back()->withErrors('401: Unauthorized!', 'message');
        }

        // Check if user exists already.
        if (User::where('email', $request->email)->first()) {
            return redirect()->back()->withErrors('406: Duplicate user account!', 'message');
        }

        // Check for invalid user role value.
        if (!Role::where('name', $request->role)->first()) {
            return redirect()->back()->withErrors('400: Invalid role value specified!', 'message');
        }

        // Generate temporary password.
        $words = explode(' ', 'And For Not Bingo Dusk Filter Ball Team Bad Store Lime Post Time Fjord Egg Zoom Ramp');
        shuffle($words);
        array_unshift($words, random_int(1, 999));
        $pwdArray = array_slice($words, 0, 5);
        $password = implode('', $pwdArray);

        // Account details.
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($password),
        ]);
        $user->save();

        // User role.
        $user->assignRole($request->role);

        // Send e-mail.
        Mail::to($user)->send(new UserAccountInfo($user, $password));
    }

    /**
     * Delete the spcified user account.
     */
    public function deleteUserAccount($memberId, $notify)
    {
        if (!auth()->user()->can('delete users')) {
            return response([
                'errorCode' => 401,
                'errorMsg' => 'Unauthorized!',
                'success' => false,
            ]);
        }
        if (auth()->user()->id === intval($memberId, 10)) {
            return response([
                'errorCode' => 406,
                'errorMsg' => 'Self-deletion is not allowed!',
                'success' => false,
            ]);
        }
        $member = User::where('id', $memberId)->first();
        if (!$member) {
            return response([
                'errorCode' => 404,
                'errorMsg' => 'Member account not found!',
                'success' => false,
            ]);
        }

        // Delete account.
        $member->delete();

        // Send notification e-mail.
        if ($notify === 'true') {
            Mail::to($member)->send(new UserAccountDeleted($member, auth()->user()->email));
        }

        return response([
            'success' => true,
        ]);
    }

    /**
     * Assign a role to the specified user.
     */
    public function setUserRole($memberId, $memberRole)
    {
        if (!auth()->user()->can('edit users')) {
            return response([
                'errorCode' => 401,
                'errorMsg' => 'Unauthorized!',
                'success' => false,
            ]);
        }
        if (auth()->user()->id === intval($memberId, 10)) {
            return response([
                'errorCode' => 406,
                'errorMsg' => 'Editing own role is not allowed!',
                'success' => false,
            ]);
        }
        if (!Role::where('name', $memberRole)->first()) {
            return response([
                'errorCode' => 400,
                'errorMsg' => 'Invalid role value specified!',
                'success' => false,
            ]);
        }
        $member = User::where('id', $memberId)->first();
        if (!$member) {
            return response([
                'errorCode' => 404,
                'errorMsg' => 'Member account not found!',
                'success' => false,
            ]);
        }

        // Clear all existing roles for the selected user and assign the new role.
        $member->syncRoles([]);
        $member->assignRole($memberRole);

        return response([
            'success' => true,
        ]);
    }

    /**
     * Get role and permissions for the current user.
     */
    public function getUserRoleInfo()
    {
        return response([
            'role' => auth()->user()->roles->value('name'),
            'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
            'success' => true,
        ]);
    }

    /**
     * Get data for the 'User account' table, assuming thet each user has only one role.
     */
    public function getUsersWithRoles()
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
        return response([
            'users' => $users,
            'userId' => auth()->user()->id,
            'roles' => Role::orderBy('id', 'desc')->get()->pluck('name'),
            'canEditUsers' => auth()->user()->can('edit users'),
            'canCreateUsers' => auth()->user()->can('create users'),
            'canDeleteUsers' => auth()->user()->can('delete users'),
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
}
