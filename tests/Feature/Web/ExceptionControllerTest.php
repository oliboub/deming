<?php

use App\Models\Exception;
use App\Models\User;

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->user    = User::factory()->user()->create();
    $this->auditor = User::factory()->auditor()->create();
});

test('guest is redirected to login', function () {
    $this->get('/exception/index')->assertRedirect('/login');
});

test('admin can list exceptions', function () {
    Exception::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/exception/index')->assertStatus(200);
});

test('user can list exceptions', function () {
    $this->actingAs($this->user)->get('/exception/index')->assertStatus(200);
});

test('auditor cannot list exceptions', function () {
    $this->actingAs($this->auditor)->get('/exception/index')->assertStatus(403);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/exception/create')->assertStatus(200);
});

test('admin can create an exception', function () {
    $this->actingAs($this->admin)
        ->post('/exception/store', [
            'name' => 'Temporary firewall exception',
            'description' => 'Required for vendor access',
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', ['name' => 'Temporary firewall exception']);
});

test('user can create an exception', function () {
    $this->actingAs($this->user)
        ->post('/exception/store', [
            'name' => 'User exception request',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', ['name' => 'User exception request']);
});

test('auditor cannot create an exception', function () {
    $this->actingAs($this->auditor)
        ->post('/exception/store', ['name' => 'Test'])
        ->assertStatus(403);
});

test('admin can view an exception', function () {
    $exception = Exception::factory()->create();
    $this->actingAs($this->admin)->get("/exception/show/{$exception->id}")->assertStatus(200);
});

test('admin can edit a draft exception', function () {
    $exception = Exception::factory()->create(['status' => Exception::STATUS_DRAFT]);
    $this->actingAs($this->admin)->get("/exception/edit/{$exception->id}")->assertStatus(200);
});

test('non-admin cannot edit a submitted exception', function () {
    $exception = Exception::factory()->submitted()->create();
    $this->actingAs($this->user)->get("/exception/edit/{$exception->id}")->assertStatus(403);
});

test('admin can edit a submitted exception', function () {
    $exception = Exception::factory()->submitted()->create();
    $this->actingAs($this->admin)->get("/exception/edit/{$exception->id}")->assertStatus(200);
});

test('admin can edit an approved exception', function () {
    $exception = Exception::factory()->create(['status' => Exception::STATUS_APPROVED]);
    $this->actingAs($this->admin)->get("/exception/edit/{$exception->id}")->assertStatus(200);
});

test('admin can update a submitted exception', function () {
    $exception = Exception::factory()->submitted()->create();

    $this->actingAs($this->admin)
        ->post('/exception/save', [
            'id' => $exception->id,
            'name' => 'Updated while submitted',
            'status' => Exception::STATUS_SUBMITTED,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', ['id' => $exception->id, 'name' => 'Updated while submitted']);
});

test('non-admin cannot update a submitted exception', function () {
    $exception = Exception::factory()->submitted()->create();

    $this->actingAs($this->user)
        ->post('/exception/save', [
            'id' => $exception->id,
            'name' => 'Should not be saved',
        ])
        ->assertStatus(403);
});

test('admin can update a draft exception', function () {
    $exception = Exception::factory()->create(['status' => Exception::STATUS_DRAFT]);

    $this->actingAs($this->admin)
        ->post('/exception/save', [
            'id' => $exception->id,
            'name' => 'Updated Exception Name',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', ['id' => $exception->id, 'name' => 'Updated Exception Name']);
});

test('admin can submit an exception', function () {
    $exception = Exception::factory()->create(['status' => Exception::STATUS_DRAFT]);

    $this->actingAs($this->admin)
        ->post('/exception/submit', ['id' => $exception->id])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', ['id' => $exception->id, 'status' => Exception::STATUS_SUBMITTED]);
});

test('admin can approve a submitted exception', function () {
    $exception = Exception::factory()->submitted()->create();

    $this->actingAs($this->admin)
        ->post('/exception/approve', ['id' => $exception->id])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', ['id' => $exception->id, 'status' => Exception::STATUS_APPROVED]);
});

test('admin can reject a submitted exception', function () {
    $exception = Exception::factory()->submitted()->create();

    $this->actingAs($this->admin)
        ->post('/exception/reject', [
            'id' => $exception->id,
            'approval_comment' => 'Not justified.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', ['id' => $exception->id, 'status' => Exception::STATUS_REJECTED]);
});

test('non-admin cannot approve an exception', function () {
    $exception = Exception::factory()->submitted()->create();
    $this->actingAs($this->user)
        ->post('/exception/approve', ['id' => $exception->id])
        ->assertStatus(403);
});

test('admin can delete an exception', function () {
    $exception = Exception::factory()->create();

    $this->actingAs($this->admin)
        ->get("/exception/delete/{$exception->id}")
        ->assertRedirect('/exception/index');

    $this->assertDatabaseMissing('exceptions', ['id' => $exception->id]);
});

test('non-admin cannot delete an exception', function () {
    $exception = Exception::factory()->create();
    $this->actingAs($this->user)
        ->get("/exception/delete/{$exception->id}")
        ->assertStatus(403);
});

test('admin can change the status directly via the edit form', function () {
    $exception = Exception::factory()->create(['status' => Exception::STATUS_DRAFT]);

    $this->actingAs($this->admin)
        ->post('/exception/save', [
            'id' => $exception->id,
            'name' => $exception->name,
            'status' => Exception::STATUS_APPROVED,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', [
        'id' => $exception->id,
        'status' => Exception::STATUS_APPROVED,
    ]);
});

test('non-admin cannot change the status even by forging the field', function () {
    $exception = Exception::factory()->create(['status' => Exception::STATUS_DRAFT]);

    $this->actingAs($this->user)
        ->post('/exception/save', [
            'id' => $exception->id,
            'name' => $exception->name,
            'status' => Exception::STATUS_APPROVED,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('exceptions', [
        'id' => $exception->id,
        'status' => Exception::STATUS_DRAFT,
    ]);
});
