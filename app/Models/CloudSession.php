<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudSession extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'customer_id',
        'station_session_id',
        'title',
        'started_at',
        'ended_at',
        'sync_status',
        'archived_at',
        'assets_deleted_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'archived_at' => 'datetime',
            'assets_deleted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(CloudSessionAsset::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
