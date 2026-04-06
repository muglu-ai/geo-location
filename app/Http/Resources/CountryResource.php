<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $include = $request->query('include', '');

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'iso2'          => $this->whenNotNull($this->iso2),
            'iso3'          => $this->iso3,
            'numeric_code'  => $this->whenNotNull($this->numeric_code),
            'phonecode'     => $this->phonecode,
            'capital'       => $this->whenNotNull($this->capital),
            'currency'      => $this->whenNotNull($this->currency),
            'currency_name' => $this->whenNotNull($this->currency_name),
            'currency_symbol' => $this->whenNotNull($this->currency_symbol),
            'tld'           => $this->whenNotNull($this->tld),
            'native'        => $this->whenNotNull($this->native),
            'population'    => $this->whenNotNull($this->population),
            'region'        => $this->whenNotNull($this->region),
            'region_id'     => $this->whenNotNull($this->region_id),
            'subregion'     => $this->whenNotNull($this->subregion),
            'subregion_id'  => $this->whenNotNull($this->subregion_id),
            'nationality'   => $this->whenNotNull($this->nationality),
            'latitude'      => $this->whenNotNull($this->latitude),
            'longitude'     => $this->whenNotNull($this->longitude),
            'emoji'         => $this->whenNotNull($this->emoji),
            'emoji_u'       => $this->whenNotNull($this->emoji_u),

            'states_count'  => $this->whenLoaded('states', fn() => $this->states->count()),
            'cities_count'  => $this->whenLoaded('cities', fn() => $this->cities->count()),

            'states' => $this->when(
                str_contains($include, 'states'),
                fn() => StateResource::collection($this->states)->resolve($request)
            ),

            'cities' => $this->when(
                str_contains($include, 'cities'),
                fn() => CityResource::collection($this->cities->take(50))->resolve($request)
            ),
        ];
    }
}