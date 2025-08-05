<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'order_column',
        'user_id',
        'project_id',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
    ];

    protected $attributes = [
        'name' => '{"en":""}',
        'slug' => '{"en":""}',
    ];

    public function projects()
    {
        return $this->morphedByMany(Project::class, 'taggable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeTags($query)
    {
        return $query->where('type', 'tag');
    }

    public function scopeTechnologies($query)
    {
        return $query->where('type', 'technology');
    }

    public function getNameAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded['en'] ?? $value;
        }
        return $value;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
    }

    public function getSlugAttribute($value)
    {
        return $value;
    }

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = $value;
    }
} 