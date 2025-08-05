<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Category;
use App\Models\Status;
use App\Models\Project;
use App\Models\Link;
use App\Models\LinkType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\ProjectSetting;
use App\Models\User;
use App\Models\Tag;

class ProjectController extends Controller
{
    public function create()
    {
        $categories = Category::all(['name', 'key']);
        $statuses = Status::where('is_active', true)->get(['name', 'key']);
        
        // Get user technologies
        $user = request()->attributes->get('user');
        $userTechnologies = [];
        
        if ($user) {
            $userTechnologies = Tag::where('type', 'technology')
                ->where('user_id', $user->id)
                ->get()
                ->pluck('name')
                ->filter()
                ->flatMap(function($name) {
                    // Extract technologies from JSON array structure
                    if (is_string($name)) {
                        $decoded = json_decode($name, true);
                        return is_array($decoded) ? $decoded : [];
                    }
                    return [];
                })
                ->unique() // Show unique technologies in dropdown
                ->values()
                ->toArray();
        }

        return Inertia::render('CreateProject', [
            'categories' => $categories,
            'statuses' => $statuses,
            'userTechnologies' => $userTechnologies,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|exists:categories,key',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|exists:status,key',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:255',
            'links' => 'nullable|array',
            'links.*.title' => 'required|string|max:255',
            'links.*.url' => 'required|url|max:500',
            'assets' => 'nullable|array',
            'assets.*' => 'file|max:10240', // 10MB max per file
            'preview_settings' => 'nullable|array',
            'preview_settings.showDescription' => 'nullable|boolean',
            'preview_settings.showCategory' => 'nullable|boolean',
            'preview_settings.showStatus' => 'nullable|boolean',
            'preview_settings.showDates' => 'nullable|boolean',
            'preview_settings.showTags' => 'nullable|boolean',
            'preview_settings.showTechnologies' => 'nullable|boolean',
            'preview_settings.showLinks' => 'nullable|boolean',
            'preview_settings.showAssets' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = $request->attributes->get('user');
        if (!$user) {
            return back()->withErrors(['user' => 'User not authenticated'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Get category and status IDs
            $category = null;
            if ($request->category) {
                $category = Category::where('key', $request->category)->first();
            }
            
            $status = null;
            if ($request->status) {
                $status = Status::where('key', $request->status)->first();
                if (!$status) {
                    throw new \Exception('Invalid status selected');
                }
            }

            // Generate project key
            $projectKey = strtolower(str_replace(' ', '-', $request->name)) . '-' . time();

            // Create project
            $project = Project::create([
                'key' => $projectKey,
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status_id' => $status ? $status->id : null,
                'category_id' => $category ? $category->id : null,
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create project settings with preview settings from form - STORE METHOD
            $previewSettings = $request->input('preview_settings', []);
            $this->createProjectSettings($project, $user, $previewSettings);

            // Handle links
            if ($request->has('links') && is_array($request->links)) {
                foreach ($request->links as $linkData) {
                    // Determine link type based on title
                    $linkTypeKey = $this->determineLinkType($linkData['title']);
                    $linkType = LinkType::where('key', $linkTypeKey)->first();
                    
                    if ($linkType) {
                        Link::create([
                            'key' => $linkType->key,
                            'name' => $linkData['title'],
                            'url' => $linkData['url'],
                            'link_type_id' => $linkType->id,
                            'linkable_id' => $project->id,
                            'linkable_type' => Project::class,
                        ]);
                    }
                }
            }

            // Handle tags
            if ($request->has('tags') && is_array($request->tags)) {
                $project->syncProjectTagsWithUser($request->tags, $user->id);
            }

            // Handle technologies
            if ($request->has('technologies') && is_array($request->technologies)) {
                $project->syncProjectTechnologiesWithUser($request->technologies, $user->id);
            }

            // Handle assets
            if ($request->hasFile('assets')) {
                foreach ($request->file('assets') as $file) {
                    if ($file->isValid()) {
                        // Determine asset type based on file extension
                        $assetTypeKey = $this->determineAssetType($file);
                        $assetType = AssetType::where('key', $assetTypeKey)->first();
                        
                        if ($assetType) {
                            $filename = time() . '_' . $file->getClientOriginalName();
                            $path = $file->storeAs('project-assets/' . $project->id, $filename, 'public');
                            
                            Asset::create([
                                'display_name' => $file->getClientOriginalName(),
                                'filename' => $path,
                                'asset_type_id' => $assetType->id,
                                'is_active' => true,
                                'assetable_id' => $project->id,
                                'assetable_type' => Project::class,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('home')->with('success', 'Project created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create project: ' . $e->getMessage()])->withInput();
        }
    }

    private function determineLinkType($title)
    {
        $title = strtolower($title);
        
        if (str_contains($title, 'github')) {
            return 'github';
        } elseif (str_contains($title, 'linkedin')) {
            return 'linkedin';
        } elseif (str_contains($title, 'demo') || str_contains($title, 'live') || str_contains($title, 'project')) {
            return 'liveurl';
        } else {
            return 'portfolio';
        }
    }

    private function determineAssetType($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = strtolower($file->getMimeType());
        
        // Image files
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']) || 
            str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        // Video files
        if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']) || 
            str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        // Resume files
        if (in_array($extension, ['pdf', 'doc', 'docx']) && 
            (str_contains(strtolower($file->getClientOriginalName()), 'resume') || 
             str_contains(strtolower($file->getClientOriginalName()), 'cv'))) {
            return 'resume';
        }
        
        // Readme files
        if (in_array($extension, ['md', 'txt']) && 
            str_contains(strtolower($file->getClientOriginalName()), 'readme')) {
            return 'readme';
        }
        
        // Default to image for now
        return 'image';
    }

    private function createProjectSettings($project, $user, $previewSettings = [])
    {
        \Log::info('Creating project settings with:', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'preview_settings' => $previewSettings
        ]);
        
        return ProjectSetting::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'show_description' => $previewSettings['showDescription'] ?? true,
            'show_category' => $previewSettings['showCategory'] ?? true,
            'show_status' => $previewSettings['showStatus'] ?? true,
            'show_dates' => $previewSettings['showDates'] ?? true,
            'show_tags' => $previewSettings['showTags'] ?? true,
            'show_technologies' => $previewSettings['showTechnologies'] ?? true,
            'show_links' => $previewSettings['showLinks'] ?? true,
            'show_assets' => $previewSettings['showAssets'] ?? true,
        ]);
    }

    public function saveProject(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|exists:categories,key',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|exists:status,key',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:255',
            'links' => 'nullable|array',
            'links.*.title' => 'required|string|max:255',
            'links.*.url' => 'required|url|max:500',
            'assets' => 'nullable|array',
            'assets.*' => 'file|max:10240',
            'user_id' => 'required|integer|exists:users,id',
            'preview_settings' => 'nullable|array',
            'preview_settings.showDescription' => 'nullable|boolean',
            'preview_settings.showCategory' => 'nullable|boolean',
            'preview_settings.showStatus' => 'nullable|boolean',
            'preview_settings.showDates' => 'nullable|boolean',
            'preview_settings.showTags' => 'nullable|boolean',
            'preview_settings.showTechnologies' => 'nullable|boolean',
            'preview_settings.showLinks' => 'nullable|boolean',
            'preview_settings.showAssets' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Get the user from the request
        $user = User::find($request->input('user_id'));
        if (!$user) {
            return back()->withErrors(['user' => 'User not found'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Get category and status IDs
            $category = null;
            if ($request->category) {
                $category = Category::where('key', $request->category)->first();
            }
            
            $status = null;
            if ($request->status) {
                $status = Status::where('key', $request->status)->first();
                if (!$status) {
                    throw new \Exception('Invalid status selected');
                }
            }

            // Generate project key
            $projectKey = strtolower(str_replace(' ', '-', $request->name)) . '-' . time();

            // Create project
            $project = Project::create([
                'key' => $projectKey,
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status_id' => $status ? $status->id : null,
                'category_id' => $category ? $category->id : null,
                'user_id' => $user->id,
                'is_public' => false,
            ]);

            // Create project settings with preview settings from form - SAVEPROJECT METHOD
            $previewSettings = $request->input('preview_settings', []);
            $this->createProjectSettings($project, $user, $previewSettings);

            // Handle links
            if ($request->has('links') && is_array($request->links)) {
                foreach ($request->links as $linkData) {
                    // Determine link type based on title
                    $linkTypeKey = $this->determineLinkType($linkData['title']);
                    $linkType = LinkType::where('key', $linkTypeKey)->first();
                    
                    if ($linkType) {
                        Link::create([
                            'key' => $linkType->key,
                            'name' => $linkData['title'],
                            'url' => $linkData['url'],
                            'link_type_id' => $linkType->id,
                            'linkable_id' => $project->id,
                            'linkable_type' => Project::class,
                        ]);
                    }
                }
                
            }

            // Handle tags
            if ($request->has('tags') && is_array($request->tags)) {
                $project->syncProjectTagsWithUser($request->tags, $user->id);
            }

            // Handle technologies
            if ($request->has('technologies') && is_array($request->technologies)) {
                $project->syncProjectTechnologiesWithUser($request->technologies, $user->id);
            }

            // Handle assets
            if ($request->hasFile('assets')) {
                foreach ($request->file('assets') as $file) {
                    if ($file->isValid()) {
                        // Determine asset type based on file extension
                        $assetTypeKey = $this->determineAssetType($file);
                        $assetType = AssetType::where('key', $assetTypeKey)->first();
                        
                        if ($assetType) {
                            $filename = time() . '_' . $file->getClientOriginalName();
                            $path = $file->storeAs('project-assets/' . $project->id, $filename, 'public');
                            
                            Asset::create([
                                'display_name' => $file->getClientOriginalName(),
                                'filename' => $path,
                                'asset_type_id' => $assetType->id,
                                'is_active' => true,
                                'assetable_id' => $project->id,
                                'assetable_type' => Project::class,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('home')->with('success', 'Project created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create project: ' . $e->getMessage()])->withInput();
        }
    }
} 