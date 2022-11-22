<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AppServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        Carbon::macro('tryParse', function ($value, $tz): ?Carbon {
            try {
                return Carbon::parse($value, $tz);
            } catch (\Exception $e) {
                return null;
            }
        });

        Str::macro(
            'cleanTitle',
            fn (string $value): string => Str::title(str_replace('_', ' ', $value)),
        );

        Str::macro('cleanSnake', fn (string $value): string => trim(Str::snake($value), '_'));

        Builder::macro('whereLike', function (array $attributes, string $searchTerm): Builder {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                }
            });

            return $this;
        });
    }
}