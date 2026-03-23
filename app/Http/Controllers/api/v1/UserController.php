<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('abilities:user.view', only: ['index', 'show']),
            new Middleware('abilities:user.create', only: ['store']),
            new Middleware('abilities:user.update', only: ['update']),
            new Middleware(['abilities:user.delete', 'can:delete,user'], only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $listOfUsers = UserResource::collection(User::where('organization_id', $user['organization_id'])->with('permissions')->paginate(15));

        return $listOfUsers;
        // todo
        // listar todos os usuários da organization
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $user = User::create($validated);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $user->permissions()->sync($permissions);
        }

        return $this->success($user, 'User created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    // This field updatePermissions of users
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();
        $permissions = Permission::whereIn('name', $validated['permissions'])->get();
        $user->permissions()->sync($permissions);

        return response()->noContent();

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }
}
