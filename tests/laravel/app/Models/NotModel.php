<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotModel
{
    use HasFactory;

    public $guarded = [];

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
