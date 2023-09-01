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
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->authorizeResource(User::class, 'account');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
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
            'success' => true,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check if user exists already.
        if (User::where('email', $request->email)->first()) {
            return redirect()->back()->withErrors('406: Duplicate user account!', 'message');
        }

        // Check for invalid user role value.
        if (!Role::where('name', $request->role)->first()) {
            return redirect()->back()->withErrors('400: Invalid role value specified!', 'message');
        }

        // Generate temporary password.
        $words = explode(' ', 'And For Not Bingo Damp Filter Ball Team Bad Store Lime Post Time Fjord Egg Zoom Kelvin Robot Hit Mat');
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

        // Send e-mail with credentials.
        Mail::to($user)->send(new UserAccountInfo($user, $password));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $account)
    {
        // Generate permission table.
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
            'name' => $account->name,
            'roles' => $roles,
            'permissions' => $table,
            'success' => true,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $account)
    {
        // Check input parameter.
        $newRole = $request->input('newRole');
        if (!Role::where('name', $newRole)->first()) {
            return response([
                'errorCode' => 400,
                'errorMsg' => 'Invalid role value specified!',
                'success' => false,
            ]);
        }

        // Clear all existing roles for the selected user and assign the new role.
        $account->syncRoles([]);
        $account->assignRole($newRole);

        return response([
            'success' => true,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $account)
    {
        $account->delete();

        // Send notification e-mail (optional).
        if ($request->input('notify')) {
            Mail::to($account)->send(new UserAccountDeleted($account, auth()->user()->email));
        }

        return response([
            'success' => true,
        ]);
    }
}
