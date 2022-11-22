<?php

namespace App\DTOs;

class Coordinates
{
    public function __construct(public float $lat, public float $lng)
    {
    }
}