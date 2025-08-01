<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    
    protected $fillable = [
        'name',
        'key',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
} 