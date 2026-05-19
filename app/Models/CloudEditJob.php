<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudEditJob extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'cloud_session_id',
        'source_asset_id',
        'cloud_template_id',
        'result_asset_id',
        'status',
        'editor_payload',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'editor_payload' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CloudSession::class, 'cloud_session_id');
    }

    public function sourceAsset(): BelongsTo
    {
        return $this->belongsTo(CloudSessionAsset::class, 'source_asset_id');
    }

    public function resultAsset(): BelongsTo
    {
        return $this->belongsTo(CloudSessionAsset::class, 'result_asset_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CloudTemplate::class, 'cloud_template_id');
    }
}
