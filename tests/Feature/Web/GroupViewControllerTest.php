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

    $this->actingAs($admin)->get('/group/toggle')->assertRedirect();
    expect(session('group_view'))->toBeTrue();

    $this->actingAs($admin)->get('/group/toggle')->assertRedirect();
    expect(session('group_view'))->toBeFalse();
});

test('forged group_view session does not bypass filtering for non-admin', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    session(['group_view' => true]);

    expect($user->seesAllData())->toBeFalse();
});
