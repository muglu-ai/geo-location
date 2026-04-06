<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StateResource;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;


class StateController extends Controller
{
    public function namesByCountry(string $countryName): JsonResponse
    {
        $cacheKey = 'state_names_country_' . md5($countryName);

        $payload = Cache::rememberForever($cacheKey, function () use ($countryName) {
            $country = Country::query()
                ->select('id', 'name')
                ->where('name', $countryName)
                ->first();

            if (!$country) {
                return [
                    'success' => false,
                    'message' => 'Country not found.',
                ];
            }

            $names = State::query()
                ->where('country_id', $country->id)
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all();

            return [
                'success' => true,
                'data' => $names,
                'meta' => [
                    'country_name' => $country->name,
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
        $cacheKey = 'states_v2_' . md5(json_encode($request->query()));

        $payload = Cache::rememberForever($cacheKey, function () use ($request) {
            $query = State::query()->orderBy('name');

            if ($countryId = $request->query('country_id')) {
                $query->where('country_id', $countryId);
            }

            if ($search = $request->query('search')) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $states = $query->paginate(100);

            return [
                'success' => true,
                'data'    => StateResource::collection($states->getCollection())->resolve($request),
                'meta'    => [
                    'current_page' => $states->currentPage(),
                    'last_page'    => $states->lastPage(),
                    'per_page'     => $states->perPage(),
                    'total'        => $states->total(),
                ]
            ];
        });

        return response()->json($payload);
    }

    public function show(State $state, Request $request): JsonResponse
    {
        $cacheKey = 'state_v2_' . $state->id . '_' . md5($request->query('include', ''));

        $payload = Cache::rememberForever($cacheKey, function () use ($state, $request) {
            $includes = array_filter(explode(',', $request->query('include', '')));

            $load = [];
            if (in_array('country', $includes, true)) {
                $load[] = 'country';
            }

            if (in_array('cities', $includes, true)) {
                $load[] = 'cities';
            }

            if (!empty($load)) {
                $state->load($load);
            }

            return [
                'success' => true,
                'data' => (new StateResource($state))->resolve($request),
            ];
        });

        return response()->json($payload);
    }
}