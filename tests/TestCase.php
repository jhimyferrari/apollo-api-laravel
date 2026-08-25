<?php

namespace Tests;

use App\Helpers\Test\PasswordHelper;
use App\Helpers\Test\StateRegistrationHelper;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function validPassword(): string
    {
        return PasswordHelper::generateValid();
    }

    protected function validIE(): string
    {
        return StateRegistrationHelper::generateIE();
    }
}
