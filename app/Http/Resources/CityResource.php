<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'country_id' => $this->whenNotNull($this->country_id),
            'state_id'  => $this->state_id,
            'external_id' => $this->whenNotNull($this->external_id),
            'latitude'  => $this->whenNotNull($this->latitude),
            'longitude' => $this->whenNotNull($this->longitude),
            'timezone'  => $this->whenNotNull($this->timezone),
            'state'     => $this->whenLoaded('state', fn() => [
                'id'   => $this->state->id,
                'name' => $this->state->name,
            ]),
        ];
    }
}