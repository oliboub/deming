<?php

use App\Models\Attribute;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/attributes')->assertRedirect('/login');
});

test('admin can list attributes', function () {
    Attribute::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/attributes')->assertStatus(200);
});

test('user can list attributes', function () {
    $this->actingAs($this->user)->get('/attributes')->assertStatus(200);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/attributes/create')->assertStatus(200);
});

test('non-admin cannot access create form', function () {
    $this->actingAs($this->user)->get('/attributes/create')->assertStatus(403);
});

test('admin can create an attribute', function () {
    // values must match the #tag format required by AttributeController regex
    $this->actingAs($this->admin)
        ->post('/attributes', [
            'name' => 'classification',
            'values' => '#public #internal #confidential',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('attributes', ['name' => 'classification']);
});

test('non-admin cannot create an attribute', function () {
    $this->actingAs($this->user)
        ->post('/attributes', ['name' => 'test', 'values' => 'a,b'])
        ->assertStatus(403);
});

test('admin can view an attribute', function () {
    $attribute = Attribute::factory()->create();
    $this->actingAs($this->admin)->get("/attributes/{$attribute->id}")->assertStatus(200);
});

test('admin can edit an attribute', function () {
    $attribute = Attribute::factory()->create();
    $this->actingAs($this->admin)->get("/attributes/{$attribute->id}/edit")->assertStatus(200);
});

test('non-admin cannot edit an attribute', function () {
    $attribute = Attribute::factory()->create();
    $this->actingAs($this->user)->get("/attributes/{$attribute->id}/edit")->assertStatus(403);
});

test('admin can update an attribute', function () {
    $attribute = Attribute::factory()->create();

    $this->actingAs($this->admin)
        ->put("/attributes/{$attribute->id}", [
            'name' => 'updated_classification',
            'values' => '#low #medium #high',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'name' => 'updated_classification']);
});

test('admin can delete an attribute', function () {
    $attribute = Attribute::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/attributes/{$attribute->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
});

test('admin can export attributes', function () {
    Attribute::factory()->count(2)->create();
    $this->actingAs($this->admin)->get('/export/attributes')->assertStatus(200);
});

test('admin can access the replace form', function () {
    Attribute::factory()->create(['values' => '#Foo #Bar']);
    $this->actingAs($this->admin)->get('/attribute/replace')->assertStatus(200);
});

test('manage button is shown to admin in the attributes list', function () {
    $this->actingAs($this->admin)
        ->get('/attributes')
        ->assertStatus(200)
        ->assertSee('/attribute/replace', false);
});

test('manage button is hidden from non-admin in the attributes list', function () {
    $user = User::factory()->user()->create();
    $this->actingAs($user)
        ->get('/attributes')
        ->assertStatus(200)
        ->assertDontSee('/attribute/replace', false);
});

test('non-admin roles cannot access the replace form', function (string $factoryState) {
    $nonAdmin = User::factory()->{$factoryState}()->create();
    $this->actingAs($nonAdmin)->get('/attribute/replace')->assertStatus(403);
})->with(['user', 'auditor', 'auditee']);

test('non-admin roles cannot submit a forged replace request', function (string $factoryState) {
    Attribute::factory()->create(['values' => '#Foo #Bar']);
    $nonAdmin = User::factory()->{$factoryState}()->create();
    $this->actingAs($nonAdmin)
        ->post('/attribute/replace', ['old_value' => '#Foo', 'new_value' => '#Baz'])
        ->assertStatus(403);

    $this->assertDatabaseHas('attributes', ['values' => '#Foo #Bar']);
})->with(['user', 'auditor', 'auditee']);

test('replace updates both controls.attributes and attributes.values', function () {
    $attribute = Attribute::factory()->create(['values' => '#Foo #Bar']);
    $control = \App\Models\Control::factory()->create(['attributes' => '#Foo #Baz']);

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '#Foo', 'new_value' => '#Qux'])
        ->assertRedirect('/attribute/replace');

    $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'values' => '#Qux #Bar']);
    $this->assertDatabaseHas('controls', ['id' => $control->id, 'attributes' => '#Qux #Baz']);
});

test('replace merges and deduplicates when the new value already exists on the row', function () {
    $attribute = Attribute::factory()->create(['values' => '#X #Y']);

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '#X', 'new_value' => '#Y'])
        ->assertRedirect('/attribute/replace');

    $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'values' => '#Y']);
});

test('replace preserves token order and leaves other tokens untouched', function () {
    $attribute = Attribute::factory()->create(['values' => '#Alpha #Foo #Beta #Gamma']);

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '#Foo', 'new_value' => '#Zeta'])
        ->assertRedirect('/attribute/replace');

    $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'values' => '#Alpha #Zeta #Beta #Gamma']);
});

test('replace rejects a new_value containing a space', function () {
    Attribute::factory()->create(['values' => '#Foo #Bar']);

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '#Foo', 'new_value' => '#New Value'])
        ->assertSessionHasErrors('new_value');
});

test('replace rejects an old_value not present in the known values list', function () {
    Attribute::factory()->create(['values' => '#Foo #Bar']);

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '#DoesNotExist', 'new_value' => '#New'])
        ->assertSessionHasErrors('old_value');
});

test('replace rejects empty fields', function () {
    Attribute::factory()->create(['values' => '#Foo #Bar']);

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '', 'new_value' => ''])
        ->assertSessionHasErrors(['old_value', 'new_value']);
});

test('replace is a no-op when the token is absent from a row', function () {
    $attribute = Attribute::factory()->create(['values' => '#Foo #Bar']);
    $other = Attribute::factory()->create(['values' => '#Other #Value']);

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '#Foo', 'new_value' => '#Baz'])
        ->assertRedirect('/attribute/replace');

    $this->assertDatabaseHas('attributes', ['id' => $other->id, 'values' => '#Other #Value']);
});

test('old_value list and validation include a token only present on an unrealised measure', function () {
    $unrealised = \App\Models\Measure::factory()->create(['attributes' => '#OnlyOnMeasure']);
    \App\Models\Measure::factory()->done()->create(['attributes' => '#OnlyOnRealisedMeasure']);

    $this->actingAs($this->admin)
        ->get('/attribute/replace')
        ->assertStatus(200)
        ->assertSee('#OnlyOnMeasure')
        ->assertDontSee('#OnlyOnRealisedMeasure');

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '#OnlyOnMeasure', 'new_value' => '#Replaced'])
        ->assertRedirect('/attribute/replace')
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('measures', ['id' => $unrealised->id, 'attributes' => '#Replaced']);
});

test('replace updates unrealised measures but leaves realised measures untouched', function () {
    Attribute::factory()->create(['values' => '#Foo #Bar']);

    $unrealised = \App\Models\Measure::factory()->create(['attributes' => '#Foo #Baz']);
    $realised = \App\Models\Measure::factory()->done()->create(['attributes' => '#Foo #Baz']);

    $this->actingAs($this->admin)
        ->post('/attribute/replace', ['old_value' => '#Foo', 'new_value' => '#Qux'])
        ->assertRedirect('/attribute/replace');

    $this->assertDatabaseHas('measures', ['id' => $unrealised->id, 'attributes' => '#Qux #Baz']);
    $this->assertDatabaseHas('measures', ['id' => $realised->id, 'attributes' => '#Foo #Baz']);
});
