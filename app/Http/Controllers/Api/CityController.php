<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CityController extends Controller
{
    public function namesByState(string $stateName): JsonResponse
    {
        $cacheKey = 'city_names_state_' . md5($stateName);

        $payload = Cache::rememberForever($cacheKey, function () use ($stateName) {
            $state = \App\Models\State::query()
                ->select('id', 'name')
                ->where('name', $stateName)
                ->first();

            if (!$state) {
                return [
                    'success' => false,
                    'message' => 'State not found.',
                ];
            }

            $names = City::query()
                ->where('state_id', $state->id)
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all();

            return [
                'success' => true,
                'data' => $names,
                'meta' => [
                    'state_name' => $state->name,
                    'total' => count($names),
                ],
            ];
        });

        if (($payload['success'] ?? false) === false) {
            return response()->json($payload, 404);
        }

        return response()->json($payload);
    }


    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'cities_v2_' . md5(json_encode($request->query()));

        $payload = Cache::rememberForever($cacheKey, function () use ($request) {
            $query = City::query()->orderBy('name');

            // Filter by state_id
            if ($stateId = $request->query('state_id')) {
                $query->where('state_id', $stateId);
            }

            // Filter by country_id (through state)
            if ($countryId = $request->query('country_id')) {
                $query->whereHas('state', fn($q) => $q->where('country_id', $countryId));
            }

            // Search by city name
            if ($search = $request->query('search')) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            // If state_id is provided → return ALL cities (no pagination) for that state
            if ($request->has('state_id') && !$request->has('search') && !$request->has('page')) {
                $cities = $query->get();    // Get all (safe because one state won't have 100k cities)

                return [
                    'success' => true,
                    'data'    => CityResource::collection($cities)->resolve($request),
                    'meta'    => ['total' => $cities->count(), 'type' => 'all']
                ];
            }

            // Paginated for large queries
            $cities = $query->paginate(100);

            return [
                'success' => true,
                'data'    => CityResource::collection($cities->getCollection())->resolve($request),
                'meta'    => [
                    'current_page' => $cities->currentPage(),
                    'last_page'    => $cities->lastPage(),
                    'per_page'     => $cities->perPage(),
                    'total'        => $cities->total(),
                ]
            ];
        });

        return response()->json($payload);
    }

    public function show(City $city): JsonResponse
    {
        $cacheKey = 'city_v2_' . $city->id;

        $payload = Cache::rememberForever($cacheKey, function () use ($city) {
            $city->load('state');

            return [
                'success' => true,
                'data'    => (new CityResource($city))->resolve(request()),
            ];
        });

        return response()->json($payload);
    }
}