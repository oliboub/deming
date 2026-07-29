<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/config')->assertRedirect('/login');
});

test('admin can view configuration', function () {
    $this->actingAs($this->admin)->get('/config')->assertStatus(200);
});

test('non-admin cannot view configuration', function () {
    $this->actingAs($this->user)->get('/config')->assertStatus(403);
});

test('admin can save configuration and gets 200 response', function () {
    // config/save returns a view (200), not a redirect, unless action=cancel
    $this->actingAs($this->admin)
        ->post('/config/save', ['action' => 'save'])
        ->assertStatus(200);
});

test('admin config save with cancel redirects to home', function () {
    $this->actingAs($this->admin)
        ->post('/config/save', ['action' => 'cancel'])
        ->assertRedirect('/');
});

test('non-admin cannot save configuration', function () {
    $this->actingAs($this->user)
        ->post('/config/save', [])
        ->assertStatus(403);
});
