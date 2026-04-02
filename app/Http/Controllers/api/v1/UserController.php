<?php

namespace App\Http\Controllers\api\v1;

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
        return $this->userService->listAll();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        return $this->success($user, 'User created successfully.', 201);
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
        $this->userService->updatePermissions($user, $request->validated());

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
