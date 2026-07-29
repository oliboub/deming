<?php

use App\Models\User;

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->user    = User::factory()->user()->create();
    $this->auditor = User::factory()->auditor()->create();
});

test('guest is redirected to login', function () {
    $this->get('/reports')->assertRedirect('/login');
});

test('admin can view reports page', function () {
    $this->actingAs($this->admin)->get('/reports')->assertStatus(200);
});

test('user can view reports page', function () {
    $this->actingAs($this->user)->get('/reports')->assertStatus(200);
});

test('auditor cannot view reports page', function () {
    $this->actingAs($this->auditor)->get('/reports')->assertStatus(403);
});

test('pilotage report requires start_date and redirects without it', function () {
    // Without start_date, controller redirects back with an error
    $this->actingAs($this->admin)->get('/reports/pilotage')->assertRedirect();
});

test('auditor cannot download pilotage report', function () {
    $this->actingAs($this->auditor)->get('/reports/pilotage')->assertStatus(403);
});

test('admin can access soa report', function () {
    // SOA generates an Excel file, check status is not 403
    $response = $this->actingAs($this->admin)->get('/reports/soa');
    $response->assertStatus(200);
});

test('auditor cannot download soa report', function () {
    $this->actingAs($this->auditor)->get('/reports/soa')->assertStatus(403);
});
