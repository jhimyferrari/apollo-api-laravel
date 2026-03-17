<?php

namespace App\Http\Controllers\api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $request->validated();

        $organization = Organization::where('document', $request['organization_document'])->get()->first();
        if (! $organization) {
            return response()->json(['message' => 'Invalid Credentials.'], 404);
        }
        $user = User::where('organization_id', $organization->id)
            ->where('email', $request['email'])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais Inválidas',
            ], 404);
        }

        $abilities = $user->permissions()->pluck('name')->toArray();

        $token = $user->createToken('api-token', $abilities)->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user], 200);
    }
}
