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
        // Detach existing tags of type 'tag'
        $this->tags()->where('type', 'tag')->detach();
        
        // Attach new tags
        foreach ($tags as $tagName) {
            $this->attachTag($tagName, 'tag');
        }
    }

    public function syncProjectTechnologies(array $technologies)
    {
        // Detach existing tags of type 'technology'
        $this->tags()->where('type', 'technology')->detach();
        
        // Attach new technologies
        foreach ($technologies as $technologyName) {
            $this->attachTag($technologyName, 'technology');
        }
    }

    public function syncProjectTagsWithUser(array $tags, int $userId)
    {
        // Get existing tag IDs of type 'tag' that are attached to this project
        $existingTagIds = $this->tags()->where('type', 'tag')->pluck('tags.id')->toArray();
        
        // Detach existing tags of type 'tag'
        if (!empty($existingTagIds)) {
            $this->tags()->detach($existingTagIds);
        }
        
        if (!empty($tags)) {
            // Create single tag row with all tags as JSON array
            $tag = Tag::create([
                'name' => json_encode($tags),
                'slug' => json_encode(array_map('strtolower', $tags)),
                'type' => 'tag',
                'user_id' => $userId,
                'project_id' => $this->id,
            ]);
            
            // Attach to project
            $this->tags()->attach($tag->id);
        }
    }

    public function syncProjectTechnologiesWithUser(array $technologies, int $userId)
    {
        // Get existing tag IDs of type 'technology' that are attached to this project
        $existingTagIds = $this->tags()->where('type', 'technology')->pluck('tags.id')->toArray();
        
        // Detach existing tags of type 'technology'
        if (!empty($existingTagIds)) {
            $this->tags()->detach($existingTagIds);
        }
        
        if (!empty($technologies)) {
            // Create single technology row with all technologies as JSON array
            $tag = Tag::create([
                'name' => json_encode($technologies),
                'slug' => json_encode(array_map('strtolower', $technologies)),
                'type' => 'technology',
                'user_id' => $userId,
                'project_id' => $this->id,
            ]);
            
            // Attach to project
            $this->tags()->attach($tag->id);
        }
    }

    private function normalizeTechnologyName(string $name): string
    {
        return strtolower(str_replace(' ', '', $name));
    }

    private function attachTagWithUser(string $name, string $type, int $userId)
    {
        // For technologies, always create new tags (allow duplicates)
        if ($type === 'technology') {
            $tag = Tag::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type,
                'user_id' => $userId,
                'project_id' => $this->id,
            ]);
        } else {
            // For non-technology tags, use the original logic
            $tag = Tag::where('type', $type)
                ->where('name', $name)
                ->first();

            if (!$tag) {
                            // Create new tag
            $tag = Tag::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type,
                'user_id' => $userId,
                'project_id' => $this->id,
            ]);
            } else {
                // Update user_id and project_id if tag exists but doesn't have them
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

        // Attach to project if not already attached
        if (!$this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->attach($tag->id);
        }
    }

    private function attachTag(string $name, string $type)
    {
        // First try to find existing tag by name and type
        $tag = Tag::where('type', $type)
            ->where('name', $name)
            ->first();

        if (!$tag) {
            // Create new tag
            $tag = Tag::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type,
                'user_id' => $this->user_id,
                'project_id' => $this->id,
            ]);
        } else {
            // Update user_id and project_id if tag exists but doesn't have them
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

        // Attach to project if not already attached
        if (!$this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->attach($tag->id);
        }
    }

    /**
     * Get the next sorting order for public projects
     */
    public static function getNextSortingOrder(int $userId): int
    {
        $maxOrder = self::where('user_id', $userId)
            ->where('is_public', true)
            ->max('sorting_order');
        
        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Recalculate sorting orders for all public projects of a user
     */
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

    /**
     * Update sorting orders for multiple projects
     */
    public static function updateSortingOrders(int $userId, array $projectIds): void
    {
        foreach ($projectIds as $index => $projectId) {
            self::where('id', $projectId)
                ->where('user_id', $userId)
                ->where('is_public', true)
                ->update(['sorting_order' => $index + 1]);
        }
    }

    /**
     * Boot method to handle automatic sorting order assignment
     */
    protected static function boot()
    {
        parent::boot();

        // When a project is made public, assign it the next sorting order
        static::updating(function ($project) {
            if ($project->isDirty('is_public') && $project->is_public && $project->sorting_order === null) {
                $project->sorting_order = self::getNextSortingOrder($project->user_id);
            }
        });

        // When a project is made private, recalculate sorting orders
        static::updated(function ($project) {
            if ($project->wasChanged('is_public') && !$project->is_public) {
                self::recalculateSortingOrders($project->user_id);
            }
        });
    }
} 