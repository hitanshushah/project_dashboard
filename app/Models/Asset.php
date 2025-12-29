<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'display_name',
        'filename',
        'asset_type_id',
        'is_active',
        'assetable_id',
        'assetable_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }

    public function assetable()
    {
        return $this->morphTo();
    }
} 