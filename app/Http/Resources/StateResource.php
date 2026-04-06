<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $include = $request->query('include', '');

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'country_id'  => $this->country_id,
            'external_id' => $this->whenNotNull($this->external_id),
            'iso2'        => $this->whenNotNull($this->iso2),
            'iso3166_2'   => $this->whenNotNull($this->iso3166_2),
            'native'      => $this->whenNotNull($this->native),
            'latitude'    => $this->whenNotNull($this->latitude),
            'longitude'   => $this->whenNotNull($this->longitude),
            'type'        => $this->whenNotNull($this->type),
            'timezone'    => $this->whenNotNull($this->timezone),
            'cities_count' => $this->whenLoaded('cities', fn() => $this->cities->count()),
            'country'     => $this->whenLoaded('country', fn() => [
                'id'   => $this->country->id,
                'name' => $this->country->name,
                'iso2' => $this->country->iso2,
            ]),
            'cities'      => $this->when(
                str_contains($include, 'cities'),
                fn() => CityResource::collection($this->cities)->resolve($request)
            ),
        ];
    }
}
