<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class ProductBuilder extends Builder
{
    public function published(): self
    {
        $this->whereNotNull('published_at')->where('published_at', '<', now());

        return $this;
    }

    public function skusMap(): array
    {
        return $this->get()->pluck('sku', 'id')->toArray();
    }
}