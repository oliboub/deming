<?php

use App\Models\Control;
use App\Models\Domain;
use App\Models\Measure;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create(['role' => User::ROLE_USER]);
    $this->other = User::factory()->create(['role' => User::ROLE_USER]);

    $this->ownedDomain = Domain::factory()->create(['title' => 'Owned Domain']);
    $ownedControl = Control::factory()->create(['domain_id' => $this->ownedDomain->id]);
    $ownedMeasure = Measure::factory()->create();
    $ownedMeasure->controls()->attach($ownedControl->id);
    $ownedMeasure->users()->attach($this->owner->id);

    $this->otherDomain = Domain::factory()->create(['title' => 'Other Domain']);
    $otherControl = Control::factory()->create(['domain_id' => $this->otherDomain->id]);
    $otherMeasure = Measure::factory()->create();
    $otherMeasure->controls()->attach($otherControl->id);
    $otherMeasure->users()->attach($this->other->id);
});

test('admin sees only their own domains when group view is off', function () {
    $admin = User::factory()->admin()->create();
    // Assign the admin to the "owned" measure instead of $this->owner
    Measure::whereHas('controls', fn ($q) => $q->where('domain_id', $this->ownedDomain->id))
        ->first()
        ->users()->attach($admin->id);

    $response = $this->actingAs($admin)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertDontSee('Other Domain');
});

test('admin sees all domains when group view is on', function () {
    $admin = User::factory()->admin()->create();
    Measure::whereHas('controls', fn ($q) => $q->where('domain_id', $this->ownedDomain->id))
        ->first()
        ->users()->attach($admin->id);

    $this->actingAs($admin)->get('/group/toggle');

    $response = $this->actingAs($admin)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertSee('Other Domain');
});

test('regular user only sees their own domains', function () {
    $response = $this->actingAs($this->owner)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertDontSee('Other Domain');
});

test('regular user stays filtered even with a forged group_view session value', function () {
    session(['group_view' => true]);

    $response = $this->actingAs($this->owner)->get('/domains');

    $response->assertSee('Owned Domain');
    $response->assertDontSee('Other Domain');
});

test('mif-group icon is only rendered for admins', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/domains')->assertSee('mif-group', false);

    $this->actingAs($this->owner)->get('/domains')->assertDontSee('mif-group', false);
});
