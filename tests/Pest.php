<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;

uses(TestCase::class)->in(__DIR__);

/**
 * A viewer to ask the gate about.
 *
 * Not persisted, because nothing here needs a database: the shipped default
 * gate reads an email off the user and every test gate is a closure. An
 * unsaved model is enough to be a subject, and it keeps these tests free of a
 * schema they would otherwise have to build.
 */
function viewer(string $email = 'ada@example.com'): Authenticatable
{
    $user = new class extends User
    {
        protected $guarded = [];
    };

    $user->email = $email;

    return $user;
}
