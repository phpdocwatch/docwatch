<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    public $casts = [
        'approved_at' => 'datetime',
    ];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function getNetLikesAttribute(): int
    {
        return $this->likes - $this->dislikes;
    }

    public function setRatingAttribute(int $value)
    {
        if ($value < 0) {
            $this->dislikes++;
        }

        if ($value > 0) {
            $this->likes++;
        }
    }

    public function preview(): Attribute
    {
        return new Attribute(
            get: fn (): ?string => ($this->comment !== null) ? substr($this->comment, 0, 100) : null,
        );
    }
}
