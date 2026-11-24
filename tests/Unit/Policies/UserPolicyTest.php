<?php

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

describe('UserPolicy', function () {
    test('update allows updating another user', function () {
        $policy = new UserPolicy;
        $actor = new User;
        $actor->id = (string) Str::uuid();
        $target = new User;
        $target->id = (string) Str::uuid();

        $response = $policy->update($actor, $target);

        expect($response->allowed())->toBeTrue();
    });

    test('update denies updating self', function () {
        $policy = new UserPolicy;
        $user = new User;
        $user->id = (string) Str::uuid();

        $response = $policy->update($user, $user);

        expect($response->denied())->toBeTrue()
            ->and($response->message())->toBe(__('errors.user_cannot_update_self'));
    });
});
