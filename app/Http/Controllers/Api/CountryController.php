<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\StateResource;
use Illuminate\Support\Facades\Cache;

class CountryController extends Controller
{
    public function names(): JsonResponse
    {
        $payload = Cache::rememberForever('country_names_v1', function () {
            $names = Country::query()
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all();

            return [
                'success' => true,
                'data' => $names,
                'meta' => [
                    'total' => count($names),
                ],
            ];
        });

        return response()->json($payload);
    }

    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'countries_all_v2_' . md5(json_encode($request->query()));

        $payload = Cache::rememberForever($cacheKey, function () use ($request) {
            $query = Country::query();

            if ($search = $request->query('search')) {
                $query->where(function ($countryQuery) use ($search) {
                    $countryQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('iso2', 'LIKE', "%{$search}%")
                        ->orWhere('iso3', 'LIKE', "%{$search}%")
                        ->orWhere('phonecode', 'LIKE', "%{$search}%")
                        ->orWhere('numeric_code', 'LIKE', "%{$search}%")
                        ->orWhere('id', 'LIKE', "%{$search}%");
                });
            }

            if ($exact = $request->query('name')) {
                $query->where('name', $exact);
            }

            if ($id = $request->query('id')) {
                $query->where('id', $id);
            }

            $query->orderBy('id');

            $countries = $query->paginate(50);

            return [
                'success' => true,
                'data'    => CountryResource::collection($countries->getCollection())->resolve($request),
                'meta'    => [
                    'current_page' => $countries->currentPage(),
                    'last_page'    => $countries->lastPage(),
                    'per_page'     => $countries->perPage(),
                    'total'        => $countries->total(),
                    'from'         => $countries->firstItem(),
                    'to'           => $countries->lastItem(),
                ]
            ];
        });

        return response()->json($payload);
    }

    public function show(Country $country, Request $request): JsonResponse
    {
        $cacheKey = 'country_v2_' . $country->id . '_' . md5($request->query('include', ''));

        $payload = Cache::rememberForever($cacheKey, function () use ($country, $request) {
            $includes = array_filter(explode(',', $request->query('include', '')));

            $load = [];
            if (in_array('states', $includes, true)) {
                $load[] = 'states';
            }

            if (in_array('cities', $includes, true)) {
                $load[] = 'cities';
            }

            if (!empty($load)) {
                $country->load($load);
            }

            return [
                'success' => true,
                'data'    => (new CountryResource($country))->resolve($request),
            ];
        });

        return response()->json($payload);
    }

    public function states(Country $country, Request $request): JsonResponse
    {
        $query = $country->states()->orderBy('id');

        if ($search = $request->query('search')) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $states = $query->paginate(100);

        return response()->json([
            'success' => true,
            'data'    => StateResource::collection($states),
            'meta'    => [
                'current_page' => $states->currentPage(),
                'last_page'    => $states->lastPage(),
                'total'        => $states->total(),
            ]
        ]);
    }
}
