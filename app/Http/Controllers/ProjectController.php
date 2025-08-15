<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
use App\Services\MinIOService;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = request()->attributes->get('user');
        if (!$user) {
            return redirect()->route('home');
        }

        // Get search, filter, and sort parameters
        $search = $request->get('search', '');
        $categories = $request->get('categories', []);
        $statuses = $request->get('statuses', []);
        $technologies = $request->get('technologies', []);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Build the query with filters
        $query = Project::with([
            'category',
            'status', 
            'links.linkType',
            'assets.assetType',
            'settings',
            'tags'
        ])->where('user_id', $user->id);

        // Apply search filter (search in name, description, and all project data regardless of settings)
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('key', 'like', '%' . $search . '%');
            });
        }

        // Apply category filter
        if (!empty($categories) && is_array($categories)) {
            $query->whereHas('category', function($q) use ($categories) {
                $q->whereIn('key', $categories);
            });
        }

        // Apply status filter
        if (!empty($statuses) && is_array($statuses)) {
            $query->whereHas('status', function($q) use ($statuses) {
                $q->whereIn('key', $statuses);
            });
        }

        // Apply technology filter
        if (!empty($technologies) && is_array($technologies)) {
            $query->whereHas('tags', function($q) use ($technologies) {
                $q->where('type', 'technology');
                foreach ($technologies as $tech) {
                    $q->where('name', 'like', '%"' . $tech . '"%');
                }
            });
        }

        // Apply sorting
        $validSortFields = ['created_at', 'updated_at', 'name'];
        $validSortDirections = ['asc', 'desc'];
        
        if (in_array($sortBy, $validSortFields) && in_array($sortDirection, $validSortDirections)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Execute query and transform data
        $projects = $query->get()->map(function($project) {
            return [
                'id' => $project->id,
                'key' => $project->key,
                'name' => $project->name,
                'description' => $project->description,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'is_public' => $project->is_public,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
                'category' => $project->category ? $project->category->key : null,
                'status' => $project->status ? $project->status->key : null,
                'tags' => $project->tags, // This uses the accessor method getTagsAttribute()
                'technologies' => $project->technologies, // This uses the accessor method getTechnologiesAttribute()
                'links' => $project->links->map(function($link) {
                    return [
                        'title' => $link->name,
                        'url' => $link->url,
                        'type' => $link->linkType ? $link->linkType->key : null
                    ];
                }),
                'assets' => $project->assets->map(function($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->display_name,
                        'path' => $asset->filename,
                        'type' => $asset->assetType ? $asset->assetType->key : null,
                        'url' => $asset->filename, // Use MinIO URL directly
                        'filename' => $asset->filename, // MinIO URL
                        'display_name' => $asset->display_name, // Original filename
                        'asset_type' => $asset->assetType ? [
                            'key' => $asset->assetType->key,
                            'name' => $asset->assetType->name
                        ] : null
                    ];
                }),
                'settings' => $project->settings ? [
                    'showDescription' => $project->settings->show_description,
                    'showCategory' => $project->settings->show_category,
                    'showStatus' => $project->settings->show_status,
                    'showDates' => $project->settings->show_dates,
                    'showTags' => $project->settings->show_tags,
                    'showTechnologies' => $project->settings->show_technologies,
                    'showLinks' => $project->settings->show_links,
                    'showAssets' => $project->settings->show_assets,
                ] : [
                    'showDescription' => true,
                    'showCategory' => true,
                    'showStatus' => true,
                    'showDates' => true,
                    'showTags' => true,
                    'showTechnologies' => true,
                    'showLinks' => true,
                    'showAssets' => true,
                ]
            ];
        });

        // Fetch categories and statuses for the ProjectCard components
        $allCategories = Category::all(['id', 'name', 'key']);
        $allStatuses = Status::where('is_active', true)->get(['id', 'name', 'key']);

        // Get all available technologies for filter dropdown
        $allTechnologies = Tag::where('type', 'technology')
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
            ->unique()
            ->values()
            ->toArray();

        return Inertia::render('Home', [
            'projects' => $projects,
            'categories' => $allCategories,
            'statuses' => $allStatuses,
            'technologies' => $allTechnologies,
            'filters' => [
                'search' => $search,
                'categories' => $categories,
                'statuses' => $statuses,
                'technologies' => $technologies,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ]
        ]);
    }

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

    public function edit(Project $project)
    {
        $user = request()->attributes->get('user');
        if (!$user || $project->user_id !== $user->id) {
            return redirect()->route('home');
        }

        // Fetch categories and statuses for the form
        $categories = Category::all(['id', 'name', 'key']);
        $statuses = Status::where('is_active', true)->get(['id', 'name', 'key']);
        $linkTypes = LinkType::all(['id', 'name', 'key']);
        $assetTypes = AssetType::all(['id', 'name', 'key']);

        // Fetch user's existing technologies for the dropdown
        $userTechnologies = Tag::where('type', 'technology')
            ->where('user_id', $user->id)
            ->get()
            ->pluck('name')
            ->filter()
            ->flatMap(function($name) {
                if (is_string($name)) {
                    $decoded = json_decode($name, true);
                    return is_array($decoded) ? $decoded : [];
                }
                return [];
            })
            ->unique()
            ->values()
            ->toArray();

        // Load project with relationships
        $project->load(['category', 'status', 'links.linkType', 'assets.assetType', 'settings', 'tags']);

        // Prepare project data for the form
        $projectData = [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'category' => $project->category ? $project->category->key : null,
            'status' => $project->status ? $project->status->key : null,
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'is_public' => $project->is_public,
            'tags' => $project->tags,
            'technologies' => $project->technologies,
            'links' => $project->links->map(function($link) {
                return [
                    'title' => $link->name,
                    'url' => $link->url,
                ];
            }),
            'assets' => $project->assets->map(function($asset) {
                return [
                    'id' => $asset->id,
                    'name' => $asset->display_name,
                    'path' => $asset->filename,
                    'url' => $asset->filename, // Use MinIO URL directly
                    'filename' => $asset->filename, // MinIO URL
                    'display_name' => $asset->display_name, // Original filename
                    'asset_type' => $asset->assetType ? [
                        'key' => $asset->assetType->key,
                        'name' => $asset->assetType->name
                    ] : null
                ];
            }),
            'preview_settings' => $project->settings ? [
                'showDescription' => $project->settings->show_description,
                'showCategory' => $project->settings->show_category,
                'showStatus' => $project->settings->show_status,
                'showDates' => $project->settings->show_dates,
                'showTags' => $project->settings->show_tags,
                'showTechnologies' => $project->settings->show_technologies,
                'showLinks' => $project->settings->show_links,
                'showAssets' => $project->settings->show_assets,
            ] : null
        ];

        return Inertia::render('EditProject', [
            'project' => $projectData,
            'categories' => $categories,
            'statuses' => $statuses,
            'linkTypes' => $linkTypes,
            'assetTypes' => $assetTypes,
            'userTechnologies' => $userTechnologies,
        ]);
    }

    public function update(Request $request, Project $project)
    {

        
        $user = request()->attributes->get('user');
        if (!$user || $project->user_id !== $user->id) {
            return back()->withErrors(['error' => 'Unauthorized'])->withInput();
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|exists:categories,key',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|exists:status,key',
            'is_public' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:255',
            'links' => 'nullable|array',
            'links.*.title' => 'required|string|max:255',
            'links.*.url' => 'required|url|max:500',
            'existingAssets' => 'nullable|array',
            'existingAssets.*.id' => 'nullable|integer|exists:assets,id',
            'existingAssets.*.display_name' => 'nullable|string|max:255',
            'existingAssets.*.filename' => 'nullable|string|max:500',
            'assets' => 'nullable|array',
            'assets.*' => 'file|max:102400', // 100MB max per file
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

            // Update project
            $project->update([
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status_id' => $status ? $status->id : null,
                'category_id' => $category ? $category->id : null,
                'is_public' => $request->input('is_public', false),
            ]);

            // Update project settings
            $previewSettings = $request->input('preview_settings', []);
            if ($project->settings) {
                $project->settings->update([
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

            // Handle links
            $project->links()->delete();
            if ($request->has('links') && is_array($request->links)) {
                foreach ($request->links as $linkData) {
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

            // Handle tags and technologies
            if ($request->has('tags') && is_array($request->tags)) {
                $project->syncProjectTagsWithUser($request->tags, $user->id);
            }

            if ($request->has('technologies') && is_array($request->technologies)) {
                $project->syncProjectTechnologiesWithUser($request->technologies, $user->id);
            }

            // Handle assets (update, add new, delete)
            $minioService = new MinIOService();
            
            // Get current assets
            $currentAssets = $project->assets()->with('assetType')->get();
            $currentAssetIds = $currentAssets->pluck('id')->toArray();
            
            // Get assets from request (existing + new)
            $requestAssets = $request->input('existingAssets', []);
            $requestAssetIds = collect($requestAssets)->pluck('id')->filter()->toArray();
            
            // Find assets to delete (in current but not in request)
            $assetsToDelete = $currentAssets->whereNotIn('id', $requestAssetIds);
            
            // Delete assets from MinIO and database
            foreach ($assetsToDelete as $asset) {
                
                // Extract filename from MinIO URL for deletion
                if ($asset->filename && $asset->assetType) {
                    $urlParts = parse_url($asset->filename);
                    $pathParts = explode('/', trim($urlParts['path'], '/'));
                    
                    // URL structure: /projectsdashboard/username/assettype/filename
                    if (count($pathParts) >= 4) {
                        $username = $pathParts[1];
                        $assetType = $pathParts[2];
                        $filename = $pathParts[3];
                        
                        // Delete from MinIO
                        $minioService->deleteFile($username, $assetType, $filename);
                    }
                }
                
                // Delete from database
                $asset->delete();
            }
            
            // Handle new file uploads
            if ($request->hasFile('assets')) {
                foreach ($request->file('assets') as $file) {
                    if ($file->isValid()) {
                        $assetTypeKey = $this->determineAssetType($file);
                        $assetType = AssetType::where('key', $assetTypeKey)->first();
                        
                        if ($assetType) {
                            $filename = time() . '_' . $file->getClientOriginalName();
                            
                            // Upload to MinIO
                            $uploadResult = $minioService->uploadFile($file, $user->username, $assetTypeKey, $filename);
                            
                            if ($uploadResult['success']) {
                                Asset::create([
                                    'display_name' => $file->getClientOriginalName(),
                                    'filename' => $uploadResult['url'], // Store MinIO URL instead of local path
                                    'asset_type_id' => $assetType->id,
                                    'is_active' => true,
                                    'assetable_id' => $project->id,
                                    'assetable_type' => Project::class,
                                ]);
                            } else {
                                throw new \Exception('Failed to upload file to MinIO: ' . ($uploadResult['error'] ?? 'Unknown error'));
                            }
                        } else {
                            throw new \Exception('Invalid asset type: ' . $assetTypeKey);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Project updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update project: ' . $e->getMessage()])->withInput();
        }
    }

    public function toggleVisibility(Request $request, Project $project)
    {
        $user = request()->attributes->get('user');
        if (!$user || $project->user_id !== $user->id) {
            return back()->withErrors(['error' => 'Unauthorized']);
        }

        try {
            $project->update([
                'is_public' => !$project->is_public
            ]);

            return back()->with('success', 'Project visibility updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update project visibility: ' . $e->getMessage()]);
        }
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
            'assets.*' => 'file|max:102400', // 100MB max per file
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
                $minioService = new MinIOService();
                
                foreach ($request->file('assets') as $file) {
                    if ($file->isValid()) {
                        // Determine asset type based on file extension
                        $assetTypeKey = $this->determineAssetType($file);
                        $assetType = AssetType::where('key', $assetTypeKey)->first();
                        
                        if ($assetType) {
                            $filename = time() . '_' . $file->getClientOriginalName();
                            
                            // Upload to MinIO
                            $uploadResult = $minioService->uploadFile($file, $user->username, $assetTypeKey, $filename);
                            
                            if ($uploadResult['success']) {
                                Asset::create([
                                    'display_name' => $file->getClientOriginalName(),
                                    'filename' => $uploadResult['url'], // Store MinIO URL instead of local path
                                    'asset_type_id' => $assetType->id,
                                    'is_active' => true,
                                    'assetable_id' => $project->id,
                                    'assetable_type' => Project::class,
                                ]);
                            } else {
                                throw new \Exception('Failed to upload file to MinIO: ' . ($uploadResult['error'] ?? 'Unknown error'));
                            }
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
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'tiff']) || 
            str_starts_with($mimeType, 'image/')) {
            return 'images';
        }
        
        // Video files
        if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v']) || 
            str_starts_with($mimeType, 'video/')) {
            return 'videos';
        }
        
        // Document files (PDFs, Word docs, Excel, etc.)
        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md', 'rtf']) || 
            str_starts_with($mimeType, 'application/pdf') ||
            str_starts_with($mimeType, 'application/msword') ||
            str_starts_with($mimeType, 'application/vnd.openxmlformats-officedocument')) {
            return 'documents';
        }
        
        // Default to others for everything else
        return 'others';
    }

    private function createProjectSettings($project, $user, $previewSettings = [])
    {
        
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
            'assets.*' => 'file|max:102400', // 100MB max per file
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
                $minioService = new MinIOService();
                
                foreach ($request->file('assets') as $file) {
                    if ($file->isValid()) {
                        // Determine asset type based on file extension
                        $assetTypeKey = $this->determineAssetType($file);
                        $assetType = AssetType::where('key', $assetTypeKey)->first();
                        
                        if ($assetType) {
                            $filename = time() . '_' . $file->getClientOriginalName();
                            
                            // Upload to MinIO
                            $uploadResult = $minioService->uploadFile($file, $user->username, $assetTypeKey, $filename);
                            
                            if ($uploadResult['success']) {
                                Asset::create([
                                    'display_name' => $file->getClientOriginalName(),
                                    'filename' => $uploadResult['url'], // Store MinIO URL instead of local path
                                    'asset_type_id' => $assetType->id,
                                    'is_active' => true,
                                    'assetable_id' => $project->id,
                                    'assetable_type' => Project::class,
                                ]);
                            } else {
                                throw new \Exception('Failed to upload file to MinIO: ' . ($uploadResult['error'] ?? 'Unknown error'));
                            }
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

    /**
     * Get the current authenticated user for public projects
     */
    public function getUserForPublicProjects()
    {
        $user = request()->attributes->get('user');
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        return response()->json(['user_id' => $user->id]);
    }

    /**
     * Get public projects by user ID - reusable method
     */
    public function getPublicProjectsByUserId($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Get filter parameters
        $request = request();
        $search = $request->get('search', '');
        $categories = $request->get('categories', []);
        $statuses = $request->get('statuses', []);
        $technologies = $request->get('technologies', []);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Build the query for public projects only
        $query = Project::with([
            'category',
            'status', 
            'links.linkType',
            'assets.assetType',
            'settings',
            'tags'
        ])->where('user_id', $user->id)
          ->where('is_public', true); // Only fetch public projects

        // Apply search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('key', 'like', '%' . $search . '%');
            });
        }

        // Apply category filter
        if (!empty($categories) && is_array($categories)) {
            $query->whereHas('category', function($q) use ($categories) {
                $q->whereIn('key', $categories);
            });
        }

        // Apply status filter
        if (!empty($statuses) && is_array($statuses)) {
            $query->whereHas('status', function($q) use ($statuses) {
                $q->whereIn('key', $statuses);
            });
        }

        // Apply technology filter
        if (!empty($technologies) && is_array($technologies)) {
            $query->whereHas('tags', function($q) use ($technologies) {
                $q->where('type', 'technology');
                foreach ($technologies as $tech) {
                    $q->where('name', 'like', '%"' . $tech . '"%');
                }
            });
        }

        // Apply sorting
        $validSortFields = ['created_at', 'updated_at', 'name'];
        $validSortDirections = ['asc', 'desc'];
        
        if (in_array($sortBy, $validSortFields) && in_array($sortDirection, $validSortDirections)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Execute query and transform data
        $projects = $query->get()->map(function($project) {
            return [
                'id' => $project->id,
                'key' => $project->key,
                'name' => $project->name,
                'description' => $project->description,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'is_public' => $project->is_public,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
                'category' => $project->category ? $project->category->key : null,
                'status' => $project->status ? $project->status->key : null,
                'tags' => $project->tags,
                'technologies' => $project->technologies,
                'links' => $project->links->map(function($link) {
                    return [
                        'title' => $link->name,
                        'url' => $link->url,
                        'type' => $link->linkType ? $link->linkType->key : null
                    ];
                }),
                'assets' => $project->assets->map(function($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->display_name,
                        'path' => $asset->filename,
                        'type' => $asset->assetType ? $asset->assetType->key : null,
                        'url' => $asset->filename,
                        'filename' => $asset->filename,
                        'display_name' => $asset->display_name,
                        'asset_type' => $asset->assetType ? [
                            'key' => $asset->assetType->key,
                            'name' => $asset->assetType->name
                        ] : null
                    ];
                }),
                'settings' => $project->settings ? [
                    'showDescription' => $project->settings->show_description,
                    'showCategory' => $project->settings->show_category,
                    'showStatus' => $project->settings->show_status,
                    'showDates' => $project->settings->show_dates,
                    'showTags' => $project->settings->show_tags,
                    'showTechnologies' => $project->settings->show_technologies,
                    'showLinks' => $project->settings->show_links,
                    'showAssets' => $project->settings->show_assets,
                ] : [
                    'showDescription' => true,
                    'showCategory' => true,
                    'showStatus' => true,
                    'showDates' => true,
                    'showTags' => true,
                    'showTechnologies' => true,
                    'showLinks' => true,
                    'showAssets' => true,
                ]
            ];
        });

        // Fetch categories and statuses for filtering
        $allCategories = Category::all(['id', 'name', 'key']);
        $allStatuses = Status::where('is_active', true)->get(['id', 'name', 'key']);

        // Get all available technologies for filter dropdown
        $allTechnologies = Tag::where('type', 'technology')
            ->where('user_id', $user->id)
            ->get()
            ->pluck('name')
            ->filter()
            ->flatMap(function($name) {
                if (is_string($name)) {
                    $decoded = json_decode($name, true);
                    return is_array($decoded) ? $decoded : [];
                }
                return [];
            })
            ->unique()
            ->values()
            ->toArray();

        // Get user profile for website-like display with processed data
        $userProfile = null;
        if ($user && $user->profile) {
            $profile = $user->profile;
            
            // Get profile photo URL
            $profilePhoto = $profile->assets()
                ->where('display_name', 'Profile Photo')
                ->whereHas('assetType', function($query) {
                    $query->where('key', 'images');
                })
                ->first();

            // Get profile links
            $links = $profile->links()->with('linkType')->get()->map(function ($link) {
                return [
                    'title' => $link->name,
                    'url' => $link->url,
                    'type' => $link->linkType->key ?? 'portfolio',
                ];
            })->toArray();

            // Get profile documents (excluding profile photo)
            $documents = $profile->assets()
                ->where('display_name', '!=', 'Profile Photo')
                ->whereHas('assetType', function($query) {
                    $query->where('key', 'documents');
                })
                ->get()
                ->map(function ($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->display_name,
                        'display_name' => $asset->display_name,
                        'url' => $asset->filename, // MinIO URL
                        'filename' => $asset->filename,
                        'type' => $asset->assetType ? $asset->assetType->key : 'documents',
                    ];
                })
                ->toArray();

            $userProfile = [
                'id' => $profile->id,
                'name' => $profile->name,
                'designation' => $profile->designation,
                'bio' => $profile->bio,
                'city' => $profile->city,
                'country' => $profile->country,
                'email' => $user->email,
                'profile_photo_url' => $profilePhoto ? $profilePhoto->filename : null,
                'links' => $links,
                'documents' => $documents,
            ];
        }

        return [
            'projects' => $projects,
            'categories' => $allCategories,
            'statuses' => $allStatuses,
            'technologies' => $allTechnologies,
            'userProfile' => $userProfile,
            'filters' => [
                'search' => $search,
                'categories' => $categories,
                'statuses' => $statuses,
                'technologies' => $technologies,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ]
        ];
    }

    public function publicProjects(Request $request)
    {
        $user = request()->attributes->get('user');
        if (!$user) {
            return redirect('/');
        }

        // Use the reusable method to get data
        $data = $this->getPublicProjectsByUserId($user->id);

        return Inertia::render('PublicProjects', $data);
    }

    /**
     * Display public projects for a specific user by user_id
     */
    public function publicProjectsByUserId($userId)
    {
        // Use the reusable method to get data
        $data = $this->getPublicProjectsByUserId($userId);

        return Inertia::render('PublicProjects', $data);
    }
} 