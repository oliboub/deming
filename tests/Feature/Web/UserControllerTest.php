<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->create(['role' => User::ROLE_USER]);
});

test('guest is redirected to login', function () {
    $this->get('/users')->assertRedirect('/login');
});

test('admin can list users', function () {
    User::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/users')->assertStatus(200);
});

test('non-admin cannot list users', function () {
    $this->actingAs($this->user)->get('/users')->assertStatus(403);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/users/create')->assertStatus(200);
});

test('non-admin cannot access create form', function () {
    $this->actingAs($this->user)->get('/users/create')->assertStatus(403);
});

test('admin can create a user', function () {
    // UserController uses password1/password2 fields (not password/password_confirmation)
    $this->actingAs($this->admin)
        ->post('/users', [
            'login' => 'newuser',
            'name' => 'New User',
            'title' => 'Analyst',
            'email' => 'newuser@example.com',
            'password1' => 'Password123!',
            'password2' => 'Password123!',
            'role' => User::ROLE_AUDITOR,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['login' => 'newuser']);
});

test('non-admin cannot create a user', function () {
    $this->actingAs($this->user)
        ->post('/users', ['login' => 'test', 'name' => 'Test'])
        ->assertStatus(403);
});

test('admin can view any user', function () {
    $target = User::factory()->create();
    $this->actingAs($this->admin)->get("/users/{$target->id}")->assertStatus(200);
});

test('user can view their own profile', function () {
    $this->actingAs($this->user)->get("/users/{$this->user->id}")->assertStatus(200);
});

test('user cannot view another user', function () {
    $other = User::factory()->create(['role' => User::ROLE_USER]);
    $this->actingAs($this->user)->get("/users/{$other->id}")->assertStatus(403);
});

test('admin can edit a user', function () {
    $target = User::factory()->create();
    $this->actingAs($this->admin)->get("/users/{$target->id}/edit")->assertStatus(200);
});

test('user can edit their own profile', function () {
    $this->actingAs($this->user)->get("/users/{$this->user->id}/edit")->assertStatus(200);
});

test('admin can update a user', function () {
    $target = User::factory()->create();

    // update requires login, name, email, role and title (NOT NULL columns)
    $this->actingAs($this->admin)
        ->put("/users/{$target->id}", [
            'login' => $target->login,
            'name' => 'Updated Name',
            'email' => $target->email,
            'title' => 'Test Title',
            'role' => $target->role,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Updated Name']);
});

test('admin can delete a user', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/users/{$target->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

test('non-admin cannot delete a user', function () {
    $target = User::factory()->create();
    $this->actingAs($this->user)->delete("/users/{$target->id}")->assertStatus(403);
});

test('admin can export users', function () {
    $this->actingAs($this->admin)->get('/export/users')->assertStatus(200);
});
