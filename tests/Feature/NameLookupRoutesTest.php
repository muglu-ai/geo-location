<?php

/** @mixin \Tests\TestCase */

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    /** @var \Tests\TestCase $this */

    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');
    config()->set('cache.default', 'array');

    DB::purge('sqlite');
    DB::connection('sqlite')->getPdo();

    Schema::connection('sqlite')->create('countries', function (Blueprint $table) {
        $table->id();
        $table->string('name')->index();
        $table->timestamps();
    });

    Schema::connection('sqlite')->create('states', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id');
        $table->string('name')->index();
        $table->timestamps();
    });

    Schema::connection('sqlite')->create('cities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id');
        $table->foreignId('state_id');
        $table->string('name')->index();
        $table->timestamps();
    });

    Cache::flush();
    $this->withoutMiddleware();
});

afterEach(function () {
    Schema::connection('sqlite')->dropIfExists('cities');
    Schema::connection('sqlite')->dropIfExists('states');
    Schema::connection('sqlite')->dropIfExists('countries');
});

test('country names route returns all country names ordered by name', function () {
    /** @var \Tests\TestCase $this */

    Country::query()->create(['name' => 'India']);
    Country::query()->create(['name' => 'Australia']);
    Country::query()->create(['name' => 'Canada']);

    $response = $this->getJson('/api/v1/countries/names');

    $response
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'data' => ['Australia', 'Canada', 'India'],
            'meta' => [
                'total' => 3,
            ],
        ]);
});

test('state names route returns states for an exact country name', function () {
    /** @var \Tests\TestCase $this */

    $india = Country::query()->create(['name' => 'India']);
    $canada = Country::query()->create(['name' => 'Canada']);

    State::query()->create(['country_id' => $india->id, 'name' => 'Gujarat']);
    State::query()->create(['country_id' => $india->id, 'name' => 'Maharashtra']);
    State::query()->create(['country_id' => $canada->id, 'name' => 'Ontario']);

    $response = $this->getJson('/api/v1/countries/name/India/states');

    $response
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'data' => ['Gujarat', 'Maharashtra'],
            'meta' => [
                'country_name' => 'India',
                'total' => 2,
            ],
        ]);
});

test('city names route returns cities for an exact state name', function () {
    /** @var \Tests\TestCase $this */

    $india = Country::query()->create(['name' => 'India']);
    $maharashtra = State::query()->create(['country_id' => $india->id, 'name' => 'Maharashtra']);
    $gujarat = State::query()->create(['country_id' => $india->id, 'name' => 'Gujarat']);

    City::query()->create([
        'country_id' => $india->id,
        'state_id' => $maharashtra->id,
        'name' => 'Mumbai',
    ]);
    City::query()->create([
        'country_id' => $india->id,
        'state_id' => $maharashtra->id,
        'name' => 'Pune',
    ]);
    City::query()->create([
        'country_id' => $india->id,
        'state_id' => $gujarat->id,
        'name' => 'Ahmedabad',
    ]);

    $response = $this->getJson('/api/v1/states/name/Maharashtra/cities');

    $response
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'data' => ['Mumbai', 'Pune'],
            'meta' => [
                'state_name' => 'Maharashtra',
                'total' => 2,
            ],
        ]);
});

test('state names route returns 404 when country name does not exist', function () {
    /** @var \Tests\TestCase $this */

    $response = $this->getJson('/api/v1/countries/name/Unknown/states');

    $response
        ->assertNotFound()
        ->assertExactJson([
            'success' => false,
            'message' => 'Country not found.',
        ]);
});

test('city names route returns 404 when state name does not exist', function () {
    /** @var \Tests\TestCase $this */

    $response = $this->getJson('/api/v1/states/name/Unknown/cities');

    $response
        ->assertNotFound()
        ->assertExactJson([
            'success' => false,
            'message' => 'State not found.',
        ]);
});