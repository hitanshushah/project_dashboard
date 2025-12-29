<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'show_description',
        'show_category',
        'show_status',
        'show_dates',
        'show_tags',
        'show_technologies',
        'show_links',
        'show_assets',
    ];

    protected $casts = [
        'show_description' => 'boolean',
        'show_category' => 'boolean',
        'show_status' => 'boolean',
        'show_dates' => 'boolean',
        'show_tags' => 'boolean',
        'show_technologies' => 'boolean',
        'show_links' => 'boolean',
        'show_assets' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 