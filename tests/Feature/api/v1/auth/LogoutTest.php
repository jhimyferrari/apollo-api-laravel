<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /v1/logout', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('revokes the current token and returns 204', function () {
        $this->withToken($this->token)
            ->postJson(route('v1.logout'))
            ->assertNoContent();

        expect($this->user->tokens()->count())->toBe(0);
    });

    it('does not accept the token again after logout', function () {
        $this->withToken($this->token)->postJson(route('v1.logout'))->assertNoContent();
        // o guard guarda o usuário em cache dentro do mesmo teste
        $this->app['auth']->forgetGuards();

        $this->withToken($this->token)
            ->postJson(route('v1.logout'))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    });

    it('revokes only the current token, keeping the other sessions', function () {
        $other = $this->user->createToken('other-device');

        $this->withToken($this->token)
            ->postJson(route('v1.logout'))
            ->assertNoContent();

        expect($this->user->tokens()->count())->toBe(1);

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $other->accessToken->id,
        ]);
    });

    it('does not revoke tokens from other users', function () {
        $otherUser = User::factory()->create();
        $otherUser->createToken('test');

        $this->withToken($this->token)->postJson(route('v1.logout'))->assertNoContent();

        expect($otherUser->tokens()->count())->toBe(1);
    });

    describe('POST /v1/logout without authentication', function () {
        it('returns 401 with the standard error format when there is no token', function () {
            $this->postJson(route('v1.logout'))
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'UNAUTHENTICATED')
                ->assertJsonStructure(['error' => ['code', 'message']]);
        });

        it('returns 401 when the token is invalid', function () {
            $this->withToken('1|token-que-nao-existe')
                ->postJson(route('v1.logout'))
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'UNAUTHENTICATED');
        });

        it('returns the message in Portuguese', function () {
            $this->postJson(route('v1.logout'))
                ->assertJsonPath('error.message', 'Você precisa estar autenticado.');
        });
    });
});
