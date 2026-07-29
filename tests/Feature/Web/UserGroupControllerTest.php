<?php

use App\Models\User;
use App\Models\UserGroup;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/groups')->assertRedirect('/login');
});

test('admin can list groups', function () {
    UserGroup::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/groups')->assertStatus(200);
});

test('non-admin cannot list groups', function () {
    $this->actingAs($this->user)->get('/groups')->assertStatus(403);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/groups/create')->assertStatus(200);
});

test('non-admin cannot access create form', function () {
    $this->actingAs($this->user)->get('/groups/create')->assertStatus(403);
});

test('admin can create a group', function () {
    $this->actingAs($this->admin)
        ->post('/groups', [
            'name' => 'Security Team',
            'description' => 'Security team members',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('user_groups', ['name' => 'Security Team']);
});

test('non-admin cannot create a group', function () {
    $this->actingAs($this->user)
        ->post('/groups', ['name' => 'Test', 'description' => 'Test'])
        ->assertStatus(403);
});

test('admin can view a group', function () {
    $group = UserGroup::factory()->create();
    $this->actingAs($this->admin)->get("/groups/{$group->id}")->assertStatus(200);
});

test('non-admin cannot view a group', function () {
    $group = UserGroup::factory()->create();
    $this->actingAs($this->user)->get("/groups/{$group->id}")->assertStatus(403);
});

test('admin can edit a group', function () {
    $group = UserGroup::factory()->create();
    $this->actingAs($this->admin)->get("/groups/{$group->id}/edit")->assertStatus(200);
});

test('admin can update a group', function () {
    $group = UserGroup::factory()->create();

    $this->actingAs($this->admin)
        ->put("/groups/{$group->id}", [
            'name' => 'Updated Group',
            'description' => 'Updated description',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('user_groups', ['id' => $group->id, 'name' => 'Updated Group']);
});

test('admin can delete a group', function () {
    $group = UserGroup::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/groups/{$group->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('user_groups', ['id' => $group->id]);
});
