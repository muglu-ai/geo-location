<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\CityController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

// Protected API Routes (Auth + Rate Limit 100/min)
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api', 'cache.response'])
    ->group(function () {

        Route::get('/countries/names', [CountryController::class, 'names']);
        Route::get('/countries/name/{countryName}/states', [StateController::class, 'namesByCountry']);
        Route::get('/states/name/{stateName}/cities', [CityController::class, 'namesByState']);

        Route::get('/countries', [CountryController::class, 'index']);
        Route::get('/countries/{country}', [CountryController::class, 'show']);
        Route::get('/countries/{country}/states', [CountryController::class, 'states']);

        Route::get('/states', [StateController::class, 'index']);
        Route::get('/states/{state}', [StateController::class, 'show']);

        Route::get('/cities', [CityController::class, 'index']);
        Route::get('/cities/{city}', [CityController::class, 'show']);
    });

// Public route to get token (for testing)
Route::post('/sanctum/token', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required',
    ]);

    // For demo: Create a user or use existing logic
    // For quick testing, you can create a personal access token manually in tinker
    // php artisan tinker
    // $user = \App\Models\User::first(); // or create one
    // $user = \App\Models\User::find(2); // or find by email
    // $user = \App\Models\User::where('email', 'LIKE', '%resapi_admin@countriesapi.com%')->first();
        // $token = $user->createToken('test-token')->plainTextToken;
        // echo $token;
});

// Public route to create master admin and get token (use only once)
Route::post('/create-master-user', function (Illuminate\Http\Request $request) {
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'resapi_admin@countriesapi.com'],
        [
            'name' => 'Admin',
            'password' => bcrypt('Qwerty@123'),   // Change this password for production!
        ]
    );

    $token = $user->createToken('master-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Master user created/verified',
        'email' => $user->email,
        'password' => 'Qwerty@123',   // Remove this line in production
        'token' => $token,
        'warning' => 'Change password immediately!'
    ]);
});

// Cache Clear Route (Protected)
// Route::post('/clear-cache', function () {
//     Cache::flush();
//     return response()->json(['message' => 'All cache cleared successfully']);
// });