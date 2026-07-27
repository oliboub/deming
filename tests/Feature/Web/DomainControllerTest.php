<?php

use App\Models\Domain;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/domains')->assertRedirect('/login');
});

test('admin can list domains', function () {
    Domain::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/domains')->assertStatus(200);
});

test('user can list domains', function () {
    $this->actingAs($this->user)->get('/domains')->assertStatus(200);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/domains/create')->assertStatus(200);
});

test('non-admin cannot access create form', function () {
    $this->actingAs($this->user)->get('/domains/create')->assertStatus(403);
});

test('admin can create a domain', function () {
    $this->actingAs($this->admin)
        ->post('/domains', [
            'framework' => 'ISO27001',
            'title' => 'Security',
            'description' => 'Security domain',
        ])
        ->assertRedirect('/domains');

    $this->assertDatabaseHas('domains', ['title' => 'Security']);
});

test('non-admin cannot create a domain', function () {
    $this->actingAs($this->user)
        ->post('/domains', [
            'framework' => 'ISO27001',
            'title' => 'Security',
            'description' => 'Security domain',
        ])
        ->assertStatus(403);
});

test('admin can view a domain', function () {
    $domain = Domain::factory()->create();
    $this->actingAs($this->admin)->get("/domains/{$domain->id}")->assertStatus(200);
});

test('admin can edit a domain', function () {
    $domain = Domain::factory()->create();
    $this->actingAs($this->admin)->get("/domains/{$domain->id}/edit")->assertStatus(200);
});

test('non-admin cannot edit a domain', function () {
    $domain = Domain::factory()->create();
    $this->actingAs($this->user)->get("/domains/{$domain->id}/edit")->assertStatus(403);
});

test('admin can update a domain', function () {
    $domain = Domain::factory()->create();
    $this->actingAs($this->admin)
        ->put("/domains/{$domain->id}", [
            'framework' => 'NIST',
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ])
        ->assertRedirect("/domains/{$domain->id}");

    $this->assertDatabaseHas('domains', ['id' => $domain->id, 'title' => 'Updated Title']);
});

test('admin can delete a domain without measures', function () {
    $domain = Domain::factory()->create();
    $this->actingAs($this->admin)
        ->delete("/domains/{$domain->id}")
        ->assertRedirect('/domains');

    $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
});

test('admin can export domains', function () {
    Domain::factory()->count(2)->create();
    $this->actingAs($this->admin)
        ->get('/export/domains')
        ->assertStatus(200);
});

test('non-admin cannot export domains', function () {
    $this->actingAs($this->user)
        ->get('/export/domains')
        ->assertStatus(403);
});
