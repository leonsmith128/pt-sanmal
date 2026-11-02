<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

function reverseGeocodeEmployee(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->forceFill([
        'role' => UserRole::Employee,
        'is_active' => true,
    ])->save();

    Employee::create(['user_id' => $user->id]);

    return $user;
}

beforeEach(function () {
    Cache::flush();
    RateLimiter::clear('attendance-nominatim-reverse-geocode');
});

it('uses Geoapify as the primary reverse geocoding provider and caches the result', function () {
    config(['services.geoapify.key' => 'test-api-key']);

    Http::fake([
        'api.geoapify.com/*' => Http::response([
            'features' => [
                ['properties' => ['formatted' => 'Geoapify address, Makassar']],
            ],
        ]),
    ]);

    $this->actingAs(reverseGeocodeEmployee());

    $url = route('attendance.reverse-geocode', [
        'latitude' => -5.14,
        'longitude' => 119.41,
    ]);

    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('address', 'Geoapify address, Makassar')
        ->assertJsonPath('provider', 'geoapify');

    $this->getJson($url)->assertOk();

    Http::assertSentCount(1);
});

it('falls back to Nominatim when Geoapify is unavailable', function () {
    config(['services.geoapify.key' => 'test-api-key']);

    Http::fake([
        'api.geoapify.com/*' => Http::response([], 503),
        'nominatim.openstreetmap.org/*' => Http::response([
            'display_name' => 'Nominatim address, Makassar',
        ]),
    ]);

    $this->actingAs(reverseGeocodeEmployee())
        ->getJson(route('attendance.reverse-geocode', [
            'latitude' => -5.14,
            'longitude' => 119.41,
        ]))
        ->assertOk()
        ->assertJsonPath('address', 'Nominatim address, Makassar')
        ->assertJsonPath('provider', 'nominatim');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'nominatim.openstreetmap.org')
        && $request->hasHeader('User-Agent'));
});

it('validates reverse geocoding coordinates', function () {
    $this->actingAs(reverseGeocodeEmployee())
        ->getJson(route('attendance.reverse-geocode', [
            'latitude' => 120,
            'longitude' => 119.41,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('latitude');
});
