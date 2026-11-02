<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class AttendanceReverseGeocoder
{
    public function resolve(float $latitude, float $longitude): array
    {
        $cacheKey = 'attendance:reverse-geocode:'.sha1(
            number_format($latitude, 5, '.', '').':'.number_format($longitude, 5, '.', '')
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($latitude, $longitude): array {
            $address = $this->fromGeoapify($latitude, $longitude);

            if ($address !== null) {
                return ['address' => $address, 'provider' => 'geoapify'];
            }

            $address = $this->fromNominatim($latitude, $longitude);

            return [
                'address' => $address,
                'provider' => $address !== null ? 'nominatim' : null,
            ];
        });
    }

    private function fromGeoapify(float $latitude, float $longitude): ?string
    {
        $apiKey = config('services.geoapify.key');

        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->get('https://api.geoapify.com/v1/geocode/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'apiKey' => $apiKey,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $address = data_get($response->json(), 'features.0.properties.formatted');

            return is_string($address) && $address !== '' ? mb_substr($address, 0, 500) : null;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function fromNominatim(float $latitude, float $longitude): ?string
    {
        try {
            $response = RateLimiter::attempt(
                'attendance-nominatim-reverse-geocode',
                1,
                fn (): Response => Http::acceptJson()
                    ->withHeaders([
                        'User-Agent' => $this->nominatimUserAgent(),
                        'Referer' => config('app.url'),
                    ])
                    ->timeout(5)
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'format' => 'jsonv2',
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'zoom' => 18,
                        'addressdetails' => 1,
                    ]),
                1,
            );

            if (! $response instanceof Response || ! $response->successful()) {
                return null;
            }

            $address = data_get($response->json(), 'display_name');

            return is_string($address) && $address !== '' ? mb_substr($address, 0, 500) : null;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function nominatimUserAgent(): string
    {
        $userAgent = config('app.name', 'SanMal').' Attendance/1.0';
        $contact = config('services.nominatim.contact');

        return is_string($contact) && $contact !== ''
            ? $userAgent.' ('.$contact.')'
            : $userAgent.' ('.config('app.url').')';
    }
}
