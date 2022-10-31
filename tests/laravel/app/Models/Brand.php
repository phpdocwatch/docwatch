<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Brand extends Model
{
    use HasFactory;

    public $guarded = [];


    public $casts = [
        'meta' => 'array',
    ];

    public function scopeEstablishedRecently(Builder $query)
    {
        $query->where('established', '>', now()->subYears(5));
    }

    public function scopeEstablishedAround(Builder $query, Carbon $year)
    {
        $query->where('established', '>', $year->copy()->subYear())
            ->where('established', '<', $year->copy()->addYear());
    }

    public function scopeAsList(Builder $query, string $label = 'name', string $id = 'id'): array
    {
        return $query->get()->pluck($label, $id)->toArray();
    }

    public function firstProduct()
    {
        return $this->hasOne(Product::class)->orderBy('id', 'asc');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
