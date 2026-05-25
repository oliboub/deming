<?php

use App\Models\Control;
use App\Models\Domain;
use App\Models\User;

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->user    = User::factory()->create(['role' => User::ROLE_USER]);
    $this->auditor = User::factory()->auditor()->create();
});

test('guest is redirected to login', function () {
    $this->get('/alice/index')->assertRedirect('/login');
});

test('admin can list measures', function () {
    Control::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/alice/index')->assertStatus(200);
});

test('auditor can list measures', function () {
    $this->actingAs($this->auditor)->get('/alice/index')->assertStatus(200);
});

test('api user cannot list measures', function () {
    $this->actingAs(User::factory()->apiUser()->create())
        ->get('/alice/index')
        ->assertStatus(403);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/alice/create')->assertStatus(200);
});

test('auditor cannot access create form', function () {
    $this->actingAs($this->auditor)->get('/alice/create')->assertStatus(403);
});

test('admin can create a measure', function () {
    $domain = Domain::factory()->create();

    $this->actingAs($this->admin)
        ->post('/alice/store', [
            'domain_id' => $domain->id,
            'clause' => '5.1.1',
            'name' => 'Access Control Policy',
            'objective' => 'Ensure access is controlled properly',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('controls', ['clause' => '5.1.1']);
});

test('non-admin user can create a measure', function () {
    $domain = Domain::factory()->create();

    $this->actingAs($this->user)
        ->post('/alice/store', [
            'domain_id' => $domain->id,
            'clause' => '5.1.2',
            'name' => 'Mobile Device Policy',
            'objective' => 'Ensure mobile device access is controlled',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('controls', ['clause' => '5.1.2']);
});

test('auditor cannot create a measure', function () {
    $domain = Domain::factory()->create();
    $this->actingAs($this->auditor)
        ->post('/alice/store', [
            'domain_id' => $domain->id,
            'clause' => '5.1.3',
            'name' => 'Policy',
            'objective' => 'Objective',
        ])
        ->assertStatus(403);
});

test('admin can view a measure', function () {
    $control = Control::factory()->create();
    $this->actingAs($this->admin)->get("/alice/show/{$control->id}")->assertStatus(200);
});

test('admin can edit a measure', function () {
    $control = Control::factory()->create();
    $this->actingAs($this->admin)->get("/alice/{$control->id}/edit")->assertStatus(200);
});

test('auditor cannot edit a measure', function () {
    $control = Control::factory()->create();
    $this->actingAs($this->auditor)->get("/alice/{$control->id}/edit")->assertStatus(403);
});

test('admin can update a measure', function () {
    $control = Control::factory()->create();

    $this->actingAs($this->admin)
        ->post("/alice/save/{$control->id}", [
            'domain_id' => $control->domain_id,
            'clause' => $control->clause,
            'name' => 'Updated Measure Name',
            'objective' => 'Updated objective text here',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('controls', ['id' => $control->id, 'name' => 'Updated Measure Name']);
});

test('admin can delete a measure', function () {
    $control = Control::factory()->create();

    $this->actingAs($this->admin)
        ->post("/alice/delete/{$control->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('controls', ['id' => $control->id]);
});

test('auditor cannot delete a measure', function () {
    $control = Control::factory()->create();
    $this->actingAs($this->auditor)
        ->post("/alice/delete/{$control->id}")
        ->assertStatus(403);
});

test('admin can export measures', function () {
    Control::factory()->count(2)->create();
    $this->actingAs($this->admin)->get('/export/alices')->assertStatus(200);
});
