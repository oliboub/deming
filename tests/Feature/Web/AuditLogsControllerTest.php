<?php

use App\Models\AuditLog;
use App\Models\User;

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->user    = User::factory()->user()->create();
    $this->auditor = User::factory()->auditor()->create();
    AuditLog::truncate();
});

test('guest is redirected to login', function () {
    $this->get('/logs')->assertRedirect('/login');
});

test('admin can list logs', function () {
    AuditLog::factory()->count(3)->create(['user_id' => $this->admin->id]);
    $this->actingAs($this->admin)->get('/logs')->assertStatus(200);
});

test('user can list logs', function () {
    $this->actingAs($this->user)->get('/logs')->assertStatus(200);
});

test('auditor cannot list logs', function () {
    $this->actingAs($this->auditor)->get('/logs')->assertStatus(403);
});

test('admin can view a log', function () {
    $log = AuditLog::factory()->create(['user_id' => $this->admin->id]);
    $this->actingAs($this->admin)->get("/logs/show/{$log->id}")->assertStatus(200);
});

test('auditor cannot view a log', function () {
    $log = AuditLog::factory()->create(['user_id' => $this->admin->id]);
    $this->actingAs($this->auditor)->get("/logs/show/{$log->id}")->assertStatus(403);
});

test('admin can view control history', function () {
    // history uses 'bob' alias for Control and requires at least one log entry
    $log = AuditLog::factory()->create([
        'user_id' => $this->admin->id,
        'subject_type' => \App\Models\Control::class,
        'subject_id' => 999,
    ]);

    $this->actingAs($this->admin)
        ->get('/logs/history/bob/999')
        ->assertStatus(200);
});

test('history returns 404 when no logs exist for subject', function () {
    $this->actingAs($this->admin)
        ->get('/logs/history/bob/99999')
        ->assertStatus(404);
});
