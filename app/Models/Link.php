<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Link extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'url',
        'link_type_id',
        'linkable_id',
        'linkable_type',
    ];

    public function linkType()
    {
        return $this->belongsTo(LinkType::class);
    }

    public function linkable()
    {
        return $this->morphTo();
    }
} 