<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'price',
        'duration_in_days',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
