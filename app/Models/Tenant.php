<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'business_name',
        'whatsapp_number',
        'email',
        'timezone',
        'status',
    ];

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
