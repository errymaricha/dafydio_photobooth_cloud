<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class CloudTemplate extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'station_template_id',
        'template_code',
        'name',
        'description',
        'category',
        'paper_size',
        'access_level',
        'price_amount',
        'price_currency',
        'preview_path',
        'source_path',
        'slots',
        'asset_manifest',
        'published_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'slots' => 'array',
            'asset_manifest' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
