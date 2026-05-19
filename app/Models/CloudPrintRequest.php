<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudPrintRequest extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'customer_id',
        'cloud_session_id',
        'cloud_session_asset_id',
        'quantity',
        'status',
        'priority',
        'payment_status',
        'station_claimed_at',
        'printed_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'station_claimed_at' => 'datetime',
            'printed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CloudSessionAsset::class, 'cloud_session_asset_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CloudSession::class, 'cloud_session_id');
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
