<?php

use App\Http\Controllers\Api\CountryController;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

test('country show caches payload arrays instead of response objects', function () {
    $country = new Country([
        'id' => 101,
        'name' => 'India',
        'iso2' => 'IN',
        'iso3' => 'IND',
        'phonecode' => '91',
        'emoji' => '🇮🇳',
    ]);

    Cache::shouldReceive('rememberForever')
        ->once()
        ->andReturnUsing(function (string $key, callable $callback) {
            $payload = $callback();

            expect($key)->toStartWith('country_v2_101_');
            expect($payload)->toBeArray();
            expect($payload['success'])->toBeTrue();
            expect($payload['data'])->toBeArray();

            return $payload;
        });

    $response = app(CountryController::class)->show(
        $country,
        Request::create('/api/v1/countries/101', 'GET')
    );

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getData(true))->toMatchArray([
        'success' => true,
        'data' => [
            'id' => 101,
            'name' => 'India',
            'iso2' => 'IN',
            'iso3' => 'IND',
            'phonecode' => '91',
            'emoji' => '🇮🇳',
        ],
    ]);
});