<?php

use App\Http\Controllers\Api\CityController;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

test('city show caches payload arrays instead of response objects', function () {
    $state = new State([
        'id' => 4006,
        'name' => 'Meghalaya',
        'country_id' => 101,
    ]);

    $city = new class extends City {
        public function load($relations)
        {
            return $this;
        }
    };
    $city->forceFill([
        'id' => 1,
        'name' => 'Shillong',
        'country_id' => 101,
        'state_id' => 4006,
    ]);

    $city->setRelation('state', $state);

    Cache::shouldReceive('rememberForever')
        ->once()
        ->andReturnUsing(function (string $key, callable $callback) {
            $payload = $callback();

            expect($key)->toBe('city_v2_1');
            expect($payload)->toBeArray();
            expect($payload['success'])->toBeTrue();
            expect($payload['data'])->toBeArray();

            return $payload;
        });

    $response = app(CityController::class)->show(
        $city,
        Request::create('/api/v1/cities/1', 'GET')
    );

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getData(true))->toMatchArray([
        'success' => true,
        'data' => [
            'id' => 1,
            'name' => 'Shillong',
            'country_id' => 101,
            'state_id' => 4006,
            'state' => [
                'id' => 4006,
                'name' => 'Meghalaya',
            ],
        ],
    ]);
});