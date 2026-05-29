<?php

uses()->group('api');

use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laravel\Passport\Passport;

beforeEach(function () {
    // Pest.php globally disables ThrottleRequests for Feature/API.
    // Re-enable it specifically so we can exercise the rate limiter.
    $this->withMiddleware(ThrottleRequests::class);
    Cache::flush();
});

// ---------------------------------------------------------------------------
// Unauthenticated (login endpoint) — rate limit by IP
// ---------------------------------------------------------------------------

test('rate limit headers are present on api responses', function () {
    Config::set('api.rate_limit', 60);

    $response = $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong']);

    $response->assertStatus(401);
    expect($response->headers->get('X-RateLimit-Limit'))->toBe('60')
        ->and($response->headers->has('X-RateLimit-Remaining'))->toBeTrue();
});

test('x-ratelimit-remaining decrements with each request', function () {
    Config::set('api.rate_limit', 10);

    $first  = $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong']);
    $second = $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong']);

    $remainingFirst  = (int) $first->headers->get('X-RateLimit-Remaining');
    $remainingSecond = (int) $second->headers->get('X-RateLimit-Remaining');

    expect($remainingSecond)->toBe($remainingFirst - 1);
});

test('unauthenticated requests are throttled after limit is exceeded', function () {
    Config::set('api.rate_limit', 3);

    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong'])
            ->assertStatus(401);
    }

    $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong'])
        ->assertStatus(429);
});

test('throttled response includes retry-after and x-ratelimit-reset headers', function () {
    Config::set('api.rate_limit', 1);

    $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong'])
        ->assertStatus(401);

    $response = $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong'])
        ->assertStatus(429);

    expect($response->headers->has('Retry-After'))->toBeTrue()
        ->and($response->headers->has('X-RateLimit-Reset'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Authenticated — rate limit by user ID
// ---------------------------------------------------------------------------

test('authenticated non-admin users are rate limited after limit is exceeded', function () {
    Config::set('api.rate_limit', 2);

    $user = User::factory()->apiUser()->create();
    Passport::actingAs($user);

    $this->postJson('/api/logout')->assertStatus(200);
    $this->postJson('/api/logout')->assertStatus(200);

    $this->postJson('/api/logout')->assertStatus(429);
});

test('rate limit is isolated per user', function () {
    Config::set('api.rate_limit', 1);

    $user1 = User::factory()->apiUser()->create();
    $user2 = User::factory()->apiUser()->create();

    // user1 exhausts their limit
    Passport::actingAs($user1);
    $this->postJson('/api/logout')->assertStatus(200);
    $this->postJson('/api/logout')->assertStatus(429);

    // user2 has an independent limit and is not affected
    Passport::actingAs($user2);
    $this->postJson('/api/logout')->assertStatus(200);
});

test('admin users bypass rate limiting entirely', function () {
    Config::set('api.rate_limit', 2);

    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    // Limit::none() for admins — no X-RateLimit headers added, never throttled
    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/api/logout');
        expect($response->status())->not->toBe(429)
            ->and($response->headers->has('X-RateLimit-Limit'))->toBeFalse();
    }
});
