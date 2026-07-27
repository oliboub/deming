<?php

use App\Models\User;

test('guest is redirected to login', function () {
    $this->get('/group/toggle')->assertRedirect('/login');
});

test('non-admin cannot toggle group view', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get('/group/toggle')
        ->assertStatus(403);

    $this->assertFalse(session('group_view', false));
});

test('auditor cannot toggle group view', function () {
    $this->actingAs(User::factory()->auditor()->create())
        ->get('/group/toggle')
        ->assertStatus(403);
});

test('admin can toggle group view on and off', function () {
    $admin = User::factory()->admin()->create();

    // Default (no session key yet) is "sees all data", so the first toggle turns it off
    $this->actingAs($admin)->get('/group/toggle')->assertRedirect();
    expect(session('group_view'))->toBeFalse();

    $this->actingAs($admin)->get('/group/toggle')->assertRedirect();
    expect(session('group_view'))->toBeTrue();
});

test('admin sees all data by default when no session preference is set', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->seesAllData())->toBeTrue();
});

test('forged group_view session does not bypass filtering for non-admin', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    session(['group_view' => true]);

    expect($user->seesAllData())->toBeFalse();
});
