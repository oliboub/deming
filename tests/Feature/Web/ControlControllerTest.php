<?php

use App\Models\Control;
use App\Models\Measure;
use App\Models\User;

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->user    = User::factory()->create(['role' => User::ROLE_USER]);
    $this->auditor = User::factory()->auditor()->create();
});

test('guest is redirected to login', function () {
    $this->get('/bob/index')->assertRedirect('/login');
});

test('admin can list controls', function () {
    Measure::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/bob/index')->assertStatus(200);
});

test('auditor can list controls', function () {
    $this->actingAs($this->auditor)->get('/bob/index')->assertStatus(200);
});

test('api user cannot list controls', function () {
    $this->actingAs(User::factory()->apiUser()->create())
        ->get('/bob/index')
        ->assertStatus(403);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/bob/create')->assertStatus(200);
});

test('auditor cannot access create form', function () {
    $this->actingAs($this->auditor)->get('/bob/create')->assertStatus(403);
});

test('admin can create a control', function () {
    $control = Control::factory()->create();

    $this->actingAs($this->admin)
        ->post('/bob/store', [
            'name' => 'Quarterly Access Review',
            'objective' => 'Review access rights each quarter',
            'periodicity' => 3,
            'plan_date' => '2026-06-01',
            'controls' => [$control->id],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('measures', ['name' => 'Quarterly Access Review']);
});

test('auditor cannot create a control', function () {
    $this->actingAs($this->auditor)
        ->post('/bob/store', [
            'name' => 'Test Control',
            'plan_date' => '2026-06-01',
        ])
        ->assertStatus(403);
});

test('admin can view a control', function () {
    $measure = Measure::factory()->create();
    $this->actingAs($this->admin)->get("/bob/show/{$measure->id}")->assertStatus(200);
});

test('auditor can view a control', function () {
    $measure = Measure::factory()->create();
    $this->actingAs($this->auditor)->get("/bob/show/{$measure->id}")->assertStatus(200);
});

test('admin can edit a control', function () {
    $measure = Measure::factory()->create();
    $this->actingAs($this->admin)->get("/bob/edit/{$measure->id}")->assertStatus(200);
});

test('non-admin cannot edit a control', function () {
    $measure = Measure::factory()->create();
    $this->actingAs($this->user)->get("/bob/edit/{$measure->id}")->assertStatus(403);
});

test('admin can delete a control', function () {
    $measure = Measure::factory()->create();
    $this->actingAs($this->admin)->get("/bob/delete/{$measure->id}")->assertRedirect();
    $this->assertDatabaseMissing('measures', ['id' => $measure->id]);
});

test('non-admin cannot delete a control', function () {
    $measure = Measure::factory()->create();
    $this->actingAs($this->user)->get("/bob/delete/{$measure->id}")->assertStatus(403);
});

test('admin can view history', function () {
    $this->actingAs($this->admin)->get('/bob/history')->assertStatus(200);
});

test('api user cannot view history', function () {
    $this->actingAs(User::factory()->apiUser()->create())
        ->get('/bob/history')
        ->assertStatus(403);
});

test('admin can view radar domains', function () {
    $this->actingAs($this->admin)->get('/radar/domains')->assertStatus(200);
});

test('admin can export controls', function () {
    Measure::factory()->count(2)->create();
    $this->actingAs($this->admin)->get('/export/bobs')->assertStatus(200);
});

test('auditor cannot export controls', function () {
    $this->actingAs($this->auditor)->get('/export/bobs')->assertStatus(403);
});
