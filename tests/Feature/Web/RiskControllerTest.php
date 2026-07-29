<?php

use App\Models\Control;
use App\Models\Risk;
use App\Models\RiskScoringConfig;
use App\Models\User;
use Database\Seeders\RiskScoringConfigSeeder;

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->user    = User::factory()->user()->create();
    $this->auditor = User::factory()->auditor()->create();
    $this->seed(RiskScoringConfigSeeder::class);

});

function activateMonarcScoringConfig(): RiskScoringConfig
{
    $config = RiskScoringConfig::create([
        'name'    => 'MONARC',
        'formula' => 'monarc',
        'is_active' => false,
        'probability_levels' => [
            ['value' => 0, 'label' => 'Non applicable', 'description' => ''],
            ['value' => 1, 'label' => 'Improbable',     'description' => ''],
            ['value' => 2, 'label' => 'Possible',       'description' => ''],
            ['value' => 3, 'label' => 'Probable',       'description' => ''],
            ['value' => 4, 'label' => 'Très probable',  'description' => ''],
        ],
        'exposure_levels'      => null,
        'vulnerability_levels' => [
            ['value' => 0, 'label' => 'Inexistante',  'description' => ''],
            ['value' => 1, 'label' => 'Très faible',  'description' => ''],
            ['value' => 2, 'label' => 'Faible',       'description' => ''],
            ['value' => 3, 'label' => 'Moyenne',      'description' => ''],
            ['value' => 4, 'label' => 'Élevée',       'description' => ''],
        ],
        'impact_levels' => [
            ['value' => 0, 'label' => 'Négligeable', 'description' => ''],
            ['value' => 1, 'label' => 'Faible',      'description' => ''],
            ['value' => 2, 'label' => 'Important',   'description' => ''],
            ['value' => 3, 'label' => 'Critique',    'description' => ''],
            ['value' => 4, 'label' => 'Vital',       'description' => ''],
        ],
        'risk_thresholds' => [
            ['level' => 'low',      'label' => 'Faible',   'max' => 16,   'color' => '#3AB87A'],
            ['level' => 'medium',   'label' => 'Moyen',    'max' => 36,   'color' => '#E09B1A'],
            ['level' => 'high',     'label' => 'Élevé',    'max' => 56,   'color' => '#E8731A'],
            ['level' => 'critical', 'label' => 'Critique', 'max' => null, 'color' => '#D94F45'],
        ],
    ]);

    $config->activate();

    return $config;
}

test('guest is redirected to login', function () {
    $this->get('/risk/index')->assertRedirect('/login');
});

test('admin can list risks', function () {
    Risk::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/risk/index')->assertStatus(200);
});

test('user can list risks', function () {
    $this->actingAs($this->user)->get('/risk/index')->assertStatus(200);
});

test('auditor can list risks', function () {
    $this->actingAs($this->auditor)->get('/risk/index')->assertStatus(200);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/risk/create')->assertStatus(200);
});

test('admin can create a risk', function () {
    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name' => 'Unauthorized data access',
            'probability' => 3,
            'impact' => 4,
            'status' => Risk::STATUS_NOT_EVALUATED,
            'review_frequency' => 12,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('risks', ['name' => 'Unauthorized data access']);
});

test('user can create a risk', function () {
    $this->actingAs($this->user)
        ->post('/risk/store', [
            'name' => 'Data breach risk',
            'probability' => 2,
            'impact' => 5,
            'status' => Risk::STATUS_NOT_EVALUATED,
            'review_frequency' => 6,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('risks', ['name' => 'Data breach risk']);
});

test('admin can view any risk', function () {
    $risk = Risk::factory()->create();
    $this->actingAs($this->admin)->get("/risk/show/{$risk->id}")->assertStatus(200);
});

test('auditor can only view their own risk', function () {
    $risk = Risk::factory()->create(['owner_id' => $this->auditor->id]);
    $this->actingAs($this->auditor)->get("/risk/show/{$risk->id}")->assertStatus(200);
});

test('auditor cannot view someone else risk', function () {
    $risk = Risk::factory()->create(['owner_id' => $this->admin->id]);
    $this->actingAs($this->auditor)->get("/risk/show/{$risk->id}")->assertStatus(403);
});

test('admin can edit a risk', function () {
    $risk = Risk::factory()->create();
    $this->actingAs($this->admin)->get("/risk/edit/{$risk->id}")->assertStatus(200);
});

test('admin can update a risk', function () {
    $risk = Risk::factory()->create();

    $this->actingAs($this->admin)
        ->post('/risk/save', [
            'id' => $risk->id,
            'name' => 'Updated Risk Name',
            'probability' => 2,
            'impact' => 3,
            'status' => Risk::STATUS_MITIGATED,
            'review_frequency' => 12,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('risks', ['id' => $risk->id, 'name' => 'Updated Risk Name']);
});

test('admin can delete a risk', function () {
    $risk = Risk::factory()->create();

    $this->actingAs($this->admin)
        ->get("/risk/delete/{$risk->id}")
        ->assertRedirect('/risk/index');

    $this->assertSoftDeleted('risks', ['id' => $risk->id]);
});

test('non-admin cannot delete a risk', function () {
    $risk = Risk::factory()->create();
    $this->actingAs($this->user)->get("/risk/delete/{$risk->id}")->assertStatus(403);
});

test('admin can view risk matrix', function () {
    $this->actingAs($this->admin)->get('/risk/matrix')->assertStatus(200);
});

test('admin can export risks', function () {
    Risk::factory()->count(2)->create();
    $this->actingAs($this->admin)->get('/export/risks')->assertStatus(200);
});

test('auditor cannot export risks', function () {
    $this->actingAs($this->auditor)->get('/export/risks')->assertStatus(403);
});

test('admin can create a mitigated risk with linked controls', function () {
    $control = Control::factory()->create();

    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name'             => 'Mitigated risk with controls',
            'probability'      => 2,
            'impact'           => 3,
            'status'           => Risk::STATUS_MITIGATED,
            'review_frequency' => 12,
            'control_ids'      => [$control->id],
        ])
        ->assertRedirect();

    $risk = Risk::where('name', 'Mitigated risk with controls')->firstOrFail();
    expect($risk->controls)->toHaveCount(1);
    expect($risk->controls->first()->id)->toBe($control->id);
});

test('mitigated risk with no controls triggers warning', function () {
    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name'             => 'Mitigated without controls',
            'probability'      => 2,
            'impact'           => 3,
            'status'           => Risk::STATUS_MITIGATED,
            'review_frequency' => 12,
        ])
        ->assertRedirect()
        ->assertSessionHas('warning');
});

// ---------------------------------------------------------------------------
// Traitement MONARC (Risk.status)
// ---------------------------------------------------------------------------

test('hors MONARC : create, edit et filtre index proposent les 7 statuts', function () {
    $risk = Risk::factory()->create();

    $this->actingAs($this->admin)->get('/risk/create')
        ->assertOk()
        ->assertSee('value="avoided"', false)
        ->assertSee('value="temporarily_accepted"', false);

    $this->actingAs($this->admin)->get("/risk/edit/{$risk->id}")
        ->assertOk()
        ->assertSee('value="avoided"', false)
        ->assertSee('value="temporarily_accepted"', false);

    $this->actingAs($this->admin)->get('/risk/index')
        ->assertOk()
        ->assertSee('value="avoided"', false)
        ->assertSee('value="temporarily_accepted"', false);
});

test('MONARC actif : create, edit et filtre index ne proposent que les 5 traitements MONARC', function () {
    activateMonarcScoringConfig();
    $risk = Risk::factory()->create();

    $this->actingAs($this->admin)->get('/risk/create')
        ->assertOk()
        ->assertDontSee('value="avoided"', false)
        ->assertDontSee('value="temporarily_accepted"', false)
        ->assertSee(trans('cruds.risk.status_monarc.shared'));

    $this->actingAs($this->admin)->get("/risk/edit/{$risk->id}")
        ->assertOk()
        ->assertDontSee('value="avoided"', false)
        ->assertDontSee('value="temporarily_accepted"', false)
        ->assertSee(trans('cruds.risk.status_monarc.shared'));

    $this->actingAs($this->admin)->get('/risk/index')
        ->assertOk()
        ->assertDontSee('value="avoided"', false)
        ->assertDontSee('value="temporarily_accepted"', false)
        ->assertSee(trans('cruds.risk.status_monarc.shared'));
});

test('MONARC actif : store persiste un statut MONARC valide et affiche le libellé MONARC', function () {
    activateMonarcScoringConfig();

    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name'             => 'Risk transferred under MONARC',
            'probability'      => 2,
            'impact'           => 3,
            'vulnerability'    => 1,
            'status'           => Risk::STATUS_TRANSFERRED,
            'review_frequency' => 12,
        ])
        ->assertRedirect();

    $risk = Risk::where('name', 'Risk transferred under MONARC')->firstOrFail();
    expect($risk->status)->toBe(Risk::STATUS_TRANSFERRED);

    $this->actingAs($this->admin)->get("/risk/show/{$risk->id}")
        ->assertOk()
        ->assertSee(trans('cruds.risk.status_monarc.shared'));
});

test('MONARC actif : store rejette un statut hors MONARC forgé', function () {
    activateMonarcScoringConfig();

    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name'             => 'Risk avoided forged under MONARC',
            'probability'      => 2,
            'impact'           => 3,
            'vulnerability'    => 1,
            'status'           => Risk::STATUS_AVOIDED,
            'review_frequency' => 12,
        ])
        ->assertSessionHasErrors('status');

    $this->assertDatabaseMissing('risks', ['name' => 'Risk avoided forged under MONARC']);
});

test('MONARC actif : update rejette un statut hors MONARC forgé', function () {
    activateMonarcScoringConfig();
    $risk = Risk::factory()->create(['status' => Risk::STATUS_TRANSFERRED]);

    $this->actingAs($this->admin)
        ->post('/risk/save', [
            'id'               => $risk->id,
            'name'             => $risk->name,
            'probability'      => 2,
            'impact'           => 3,
            'vulnerability'    => 1,
            'status'           => Risk::STATUS_TEMPORARILY_ACCEPTED,
            'review_frequency' => 12,
        ])
        ->assertSessionHasErrors('status');

    expect($risk->fresh()->status)->toBe(Risk::STATUS_TRANSFERRED);
});

// ---------------------------------------------------------------------------
// Risque résiduel (residual_risk)
// ---------------------------------------------------------------------------

test('residual_risk saisi est persisté au store et à l\'update', function () {
    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name'             => 'Residual risk on store',
            'probability'      => 2,
            'impact'           => 3,
            'status'           => Risk::STATUS_NOT_EVALUATED,
            'review_frequency' => 12,
            'residual_risk'    => 4,
        ])
        ->assertRedirect();

    $risk = Risk::where('name', 'Residual risk on store')->firstOrFail();
    expect($risk->residual_risk)->toBe(4);

    $this->actingAs($this->admin)
        ->post('/risk/save', [
            'id'               => $risk->id,
            'name'             => $risk->name,
            'probability'      => 2,
            'impact'           => 3,
            'status'           => Risk::STATUS_NOT_EVALUATED,
            'review_frequency' => 12,
            'residual_risk'    => 7,
        ])
        ->assertRedirect();

    expect($risk->fresh()->residual_risk)->toBe(7);
});

test('residual_risk vide est stocké à null et non à 0', function () {
    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name'             => 'Residual risk empty',
            'probability'      => 2,
            'impact'           => 3,
            'status'           => Risk::STATUS_NOT_EVALUATED,
            'review_frequency' => 12,
        ])
        ->assertRedirect();

    $risk = Risk::where('name', 'Residual risk empty')->firstOrFail();
    expect($risk->residual_risk)->toBeNull();
});

test('hors MONARC : le champ residual_risk n\'est affiché ni sur create, ni edit, ni index, ni show', function () {
    $risk = Risk::factory()->create(['residual_risk' => 3]);

    $this->actingAs($this->admin)->get('/risk/create')
        ->assertOk()
        ->assertDontSee('name="residual_risk"', false);

    $this->actingAs($this->admin)->get("/risk/edit/{$risk->id}")
        ->assertOk()
        ->assertDontSee('name="residual_risk"', false);

    $this->actingAs($this->admin)->get('/risk/index')
        ->assertOk()
        ->assertDontSee(trans('cruds.risk.fields.residual_risk'));

    $this->actingAs($this->admin)->get("/risk/show/{$risk->id}")
        ->assertOk()
        ->assertDontSee(trans('cruds.risk.fields.residual_risk'));
});

test('MONARC actif : le champ residual_risk est affiché sur create, edit, index et show', function () {
    activateMonarcScoringConfig();
    $risk = Risk::factory()->create(['residual_risk' => 3]);

    $this->actingAs($this->admin)->get('/risk/create')
        ->assertOk()
        ->assertSee('name="residual_risk"', false);

    $this->actingAs($this->admin)->get("/risk/edit/{$risk->id}")
        ->assertOk()
        ->assertSee('name="residual_risk"', false);

    $this->actingAs($this->admin)->get('/risk/index')
        ->assertOk()
        ->assertSee(trans('cruds.risk.fields.residual_risk'));

    $this->actingAs($this->admin)->get("/risk/show/{$risk->id}")
        ->assertOk()
        ->assertSee(trans('cruds.risk.fields.residual_risk'));
});

test('residual_risk négatif est rejeté', function () {
    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name'             => 'Residual risk negative',
            'probability'      => 2,
            'impact'           => 3,
            'status'           => Risk::STATUS_NOT_EVALUATED,
            'review_frequency' => 12,
            'residual_risk'    => -1,
        ])
        ->assertSessionHasErrors('residual_risk');

    $this->assertDatabaseMissing('risks', ['name' => 'Residual risk negative']);
});
