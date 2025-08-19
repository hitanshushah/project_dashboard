<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use SoftDeletes;
    
    protected $table = 'profiles';
    
    protected $fillable = [
        'user_id',
        'name',
        'designation',
        'bio',
        'street',
        'city',
        'province',
        'country',
        'public_url',
        'share_profile',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function links()
    {
        return $this->morphMany(Link::class, 'linkable');
    }

    public function assets()
    {
        return $this->morphMany(Asset::class, 'assetable');
    }
}
