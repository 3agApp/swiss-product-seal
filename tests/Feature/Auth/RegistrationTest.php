<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'role' => UserRole::Supplier->value,
    ]);
    expect($user->role)->toBe(UserRole::Supplier);

    $response->assertRedirect(route('dashboard', absolute: false));
});
