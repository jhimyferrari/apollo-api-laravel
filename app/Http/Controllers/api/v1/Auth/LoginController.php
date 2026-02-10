<?php

namespace App\Http\Controllers\api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'organization_document' => ['required'],
        ]);

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

        $token = 'sdsd'; // $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user], 200);
    }
}
