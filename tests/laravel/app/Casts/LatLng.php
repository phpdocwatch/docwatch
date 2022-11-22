<?php

namespace App\Casts;

use App\DTOs\Coordinates;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class LatLng implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): Coordinates
    {
        return new Coordinates($attributes['lat'], $attributes['lng']);
    }

    public function set($model, string $key, $value, array $attributes)
    {
    }
}