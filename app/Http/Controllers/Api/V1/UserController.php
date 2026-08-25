<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(
        protected UserService $userService) {}

    public static function middleware()
    {
        return [
            new Middleware('abilities:'.PermissionType::USER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::USER_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::USER_UPDATE->value, only: ['update']),
            new Middleware(['abilities:'.PermissionType::USER_DELETE->value, 'can:delete,user'], only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return UserResource::collection(User::with('permissions')->paginate(15));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $newUser = $this->userService->create($request->validated(), Auth()->user());

        return $this->success($newUser, 'User created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return UserResource::make($user);
    }

    /**
     * Update the specified resource in storage.
     */
    // This field updatePermissions of users
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->updatePermissions($user, $request->validated()['permissions']);

        return response()->noContent();

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {

        $this->userService->delete($user);

        return response()->noContent();
    }
}
