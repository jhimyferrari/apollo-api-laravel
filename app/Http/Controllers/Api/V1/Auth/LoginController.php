<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\DocumentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $document = DocumentHelper::remove_pontuation($validated['organization_document']);
        $organization = Organization::where('document', $document)->first();
        if (! $organization) {
            return response()->json(['message' => __('auth.failed')], 404);
        }
        $user = User::where('organization_id', $organization->id)
            ->where('email', $request['email'])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 404);
        }

        $abilities = $user->permissions()->pluck('name')->toArray();

        $token = $user->createToken('api-token', $abilities)->plainTextToken;

        $user->load('permissions');

        return $this->success([
            'token' => $token,
            'user' => $user,
        ], statusCode: 200);
    }

    public function logout(): Response
    {
        Auth()->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
