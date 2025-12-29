<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'description',
        'start_date',
        'end_date',
        'status_id',
        'category_id',
        'user_id',
        'is_public',
        'sorting_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function links()
    {
        return $this->morphMany(Link::class, 'linkable');
    }

    public function assets()
    {
        return $this->morphMany(Asset::class, 'assetable');
    }

    public function settings()
    {
        return $this->hasOne(ProjectSetting::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function getTagsAttribute()
    {
        $tagRow = $this->tags()->where('type', 'tag')->first();
        if (!$tagRow) return [];
        
        $name = $tagRow->name;
        if (is_string($name)) {
            $decoded = json_decode($name, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function getTechnologiesAttribute()
    {
        $techRow = $this->tags()->where('type', 'technology')->first();
        if (!$techRow) return [];
        
        $name = $techRow->name;
        if (is_string($name)) {
            $decoded = json_decode($name, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function getProjectTagsAttribute()
    {
        $tagRow = $this->tags()->where('type', 'tag')->where('project_id', $this->id)->first();
        if (!$tagRow) return [];
        
        $name = $tagRow->name;
        if (is_string($name)) {
            $decoded = json_decode($name, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function getProjectTechnologiesAttribute()
    {
        $techRow = $this->tags()->where('type', 'technology')->where('project_id', $this->id)->first();
        if (!$techRow) return [];
        
        $name = $techRow->name;
        if (is_string($name)) {
            $decoded = json_decode($name, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function syncProjectTags(array $tags)
    {
        
        $this->tags()->where('type', 'tag')->detach();
        
        
        foreach ($tags as $tagName) {
            $this->attachTag($tagName, 'tag');
        }
    }

    public function syncProjectTechnologies(array $technologies)
    {
        
        $this->tags()->where('type', 'technology')->detach();
        
        
        foreach ($technologies as $technologyName) {
            $this->attachTag($technologyName, 'technology');
        }
    }

    public function syncProjectTagsWithUser(array $tags, int $userId)
    {
        
        $existingTagIds = $this->tags()->where('type', 'tag')->pluck('tags.id')->toArray();
        
        
        if (!empty($existingTagIds)) {
            $this->tags()->detach($existingTagIds);
            
            
            foreach ($existingTagIds as $tagId) {
                $tag = Tag::find($tagId);
                if ($tag && $tag->type === 'tag') {
                    $tag->delete();
                }
            }
        }
        
        if (!empty($tags)) {
            
            $tag = Tag::create([
                'name' => json_encode($tags),
                'slug' => json_encode(array_map('strtolower', $tags)),
                'type' => 'tag',
                'user_id' => $userId,
                'project_id' => $this->id,
            ]);
            
            
            $this->tags()->attach($tag->id);
        }
    }

    public function syncProjectTechnologiesWithUser(array $technologies, int $userId)
    {
        
        $existingTagIds = $this->tags()->where('type', 'technology')->pluck('tags.id')->toArray();
        
        
        if (!empty($existingTagIds)) {
            $this->tags()->detach($existingTagIds);
            
            
            foreach ($existingTagIds as $tagId) {
                $tag = Tag::find($tagId);
                if ($tag && $tag->type === 'technology') {
                    $tag->delete();
                }
            }
        }
        
        if (!empty($technologies)) {
            
            $tag = Tag::create([
                'name' => json_encode($technologies),
                'slug' => json_encode(array_map('strtolower', $technologies)),
                'type' => 'technology',
                'user_id' => $userId,
                'project_id' => $this->id,
            ]);
            
            
            $this->tags()->attach($tag->id);
        }
    }

    private function normalizeTechnologyName(string $name): string
    {
        return strtolower(str_replace(' ', '', $name));
    }

    private function attachTagWithUser(string $name, string $type, int $userId)
    {
        
        if ($type === 'technology') {
            $tag = Tag::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type,
                'user_id' => $userId,
                'project_id' => $this->id,
            ]);
        } else {
            
            $tag = Tag::where('type', $type)
                ->where('name', $name)
                ->first();

            if (!$tag) {
                            
            $tag = Tag::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type,
                'user_id' => $userId,
                'project_id' => $this->id,
            ]);
            } else {
                
                $updates = [];
                if ($tag->user_id === null) {
                    $updates['user_id'] = $userId;
                }
                if ($tag->project_id === null) {
                    $updates['project_id'] = $this->id;
                }
                if (!empty($updates)) {
                    $tag->update($updates);
                }
            }
        }

        
        if (!$this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->attach($tag->id);
        }
    }

    private function attachTag(string $name, string $type)
    {
        
        $tag = Tag::where('type', $type)
            ->where('name', $name)
            ->first();

        if (!$tag) {
            
            $tag = Tag::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type,
                'user_id' => $this->user_id,
                'project_id' => $this->id,
            ]);
        } else {
            
            $updates = [];
            if ($tag->user_id === null) {
                $updates['user_id'] = $this->user_id;
            }
            if ($tag->project_id === null) {
                $updates['project_id'] = $this->id;
            }
            if (!empty($updates)) {
                $tag->update($updates);
            }
        }

        
        if (!$this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->attach($tag->id);
        }
    }

    public static function getNextSortingOrder(int $userId): int
    {
        $maxOrder = self::where('user_id', $userId)
            ->where('is_public', true)
            ->max('sorting_order');
        
        return ($maxOrder ?? 0) + 1;
    }

    public static function recalculateSortingOrders(int $userId): void
    {
        $publicProjects = self::where('user_id', $userId)
            ->where('is_public', true)
            ->orderBy('sorting_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($publicProjects as $index => $project) {
            $project->update(['sorting_order' => $index + 1]);
        }
    }

    public static function updateSortingOrders(int $userId, array $projectIds): void
    {
        foreach ($projectIds as $index => $projectId) {
            self::where('id', $projectId)
                ->where('user_id', $userId)
                ->where('is_public', true)
                ->update(['sorting_order' => $index + 1]);
        }
    }

    protected static function boot()
    {
        parent::boot();

        
        static::updating(function ($project) {
            if ($project->isDirty('is_public') && $project->is_public && $project->sorting_order === null) {
                $project->sorting_order = self::getNextSortingOrder($project->user_id);
            }
        });

        
        static::updated(function ($project) {
            if ($project->wasChanged('is_public') && !$project->is_public) {
                self::recalculateSortingOrders($project->user_id);
            }
        });
    }
} 