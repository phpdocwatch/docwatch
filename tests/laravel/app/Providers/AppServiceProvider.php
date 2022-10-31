<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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

        Builder::macro('whereLike', function ($attributes, string $searchTerm) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                }
            });

            return $this;
        });
    }
}