<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enum\ErrorType;
use App\Helpers\DocumentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Hash bcrypt válido usado para manter tempo constante de verificação
     * quando organização ou usuário não existem (mitigação de timing attack).
     */
    private const string DUMMY_PASSWORD_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $document = DocumentHelper::remove_pontuation($validated['organization_document']);
        $email = Str::lower(trim($validated['email']));

        $organization = Organization::where('document', $document)->first();
        $user = $organization
            ? User::where('organization_id', $organization->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first()
            : null;

        if (! $organization || ! $user) {
            Hash::check($validated['password'], self::DUMMY_PASSWORD_HASH);

            return response()->json([
                'error' => [
                    'code' => ErrorType::Unauthenticated,
                    'message' => __('auth.failed'),
                ],
            ], 401);
        }

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'error' => [
                    'code' => ErrorType::Unauthenticated,
                    'message' => __('auth.failed'),
                ],
            ], 401);
        }

        $abilities = $user->permissions()->pluck('name')->toArray();

        $expirationMinutes = config('sanctum.expiration');
        $expiresAt = $expirationMinutes !== null
            ? now()->addMinutes((int) $expirationMinutes)
            : null;

        $newAccessToken = $user->createToken('api-token', $abilities, $expiresAt);

        $user->load('permissions');

        return $this->success([
            'token' => (string) $newAccessToken->plainTextToken,
            'expires_at' => $expiresAt?->toIso8601String(),
            'user' => UserResource::make($user),
        ], statusCode: 200);
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('permissions');

        return UserResource::make($user);
    }

    public function logout(Request $request): Response
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->noContent();
    }
}
