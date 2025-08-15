<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Models\Link;
use App\Models\LinkType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Services\MinIOService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the user's profile.
     */
    public function edit(Request $request)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return redirect()->route('home')->with('error', 'User not found');
        }

        // Get or create profile for the user
        $profile = $user->profile;
        if (!$profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;
        }

        // Get existing links
        $existingLinks = $profile->id ? $profile->links()->with('linkType')->get() : collect();
        $links = $existingLinks->map(function ($link) {
            return [
                'title' => $link->name,
                'url' => $link->url,
                'type' => $link->linkType->key ?? 'portfolio',
            ];
        })->toArray();

        // Get existing assets
        $existingAssets = $profile->id ? $profile->assets()->with('assetType')->get() : collect();
        $assets = $existingAssets->map(function ($asset) {
            return [
                'id' => $asset->id,
                'name' => $asset->display_name,
                'filename' => $asset->filename,
                'url' => $asset->filename, // MinIO URL
                'display_name' => $asset->display_name,
                'asset_type' => $asset->assetType ? [
                    'key' => $asset->assetType->key,
                    'name' => $asset->assetType->name
                ] : null
            ];
        })->toArray();

        return Inertia::render('EditProfile', [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'profile' => [
                'name' => $profile->name,
                'designation' => $profile->designation,
                'bio' => $profile->bio,
                'street' => $profile->street,
                'city' => $profile->city,
                'province' => $profile->province,
                'country' => $profile->country,
                'links' => $links,
                'assets' => $assets,
            ]
        ]);
    }

    /**
     * Update the user's profile in storage.
     */
    public function update(Request $request)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return redirect()->route('home')->with('error', 'User not found');
        }

                $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'links' => 'nullable|array',
            'links.*.title' => 'nullable|string|max:255',
            'links.*.url' => 'nullable|string',
            'assets' => 'nullable|array',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);
        // Get or create profile for the user
        $profile = $user->profile;
        if (!$profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;
        }

        // Update profile with validated data (excluding links, assets, and profile_photo)
        $profileData = array_diff_key($validatedData, array_flip(['links', 'assets', 'profile_photo']));
        

        
        $profile->fill($profileData);
        $profile->save();

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $minioService = new MinIOService();
            $bucketName = $user->username;
            
            $file = $request->file('profile_photo');
            $filename = time() . '_profile_photo.' . $file->getClientOriginalExtension();
            $filePath = 'profile/' . $filename;
            
            // Delete existing profile photo if any
            $existingProfilePhoto = $profile->assets()
                ->where('display_name', 'Profile Photo')
                ->whereHas('assetType', function($query) {
                    $query->where('key', 'images');
                })
                ->first();
            
            if ($existingProfilePhoto) {
                // Delete from MinIO - extract filename from the stored URL
                // The URL format is: http://localhost:4811/projectsdashboard/username/images/filename.jpg
                $urlParts = explode('/', $existingProfilePhoto->filename);
                $existingFilename = end($urlParts); // Get the last part which is the actual filename
                $minioService->deleteFile($bucketName, 'images', $existingFilename);
                // Delete from database
                $existingProfilePhoto->delete();
            }
            
            // Upload new profile photo
            $uploadResult = $minioService->uploadFile($file, $bucketName, 'images', $filename);
            
            if ($uploadResult['success']) {
                $assetType = AssetType::where('key', 'images')->first();
                
                if ($assetType) {
                    Asset::create([
                        'display_name' => 'Profile Photo',
                        'filename' => $uploadResult['url'],
                        'asset_type_id' => $assetType->id,
                        'is_active' => true,
                        'assetable_id' => $profile->id,
                        'assetable_type' => Profile::class,
                    ]);
                    

                }
            }
        }



        // Handle links
        if ($request->has('links') && is_array($request->links)) {
            
            // Get existing links
            $existingLinks = $profile->links()->get()->keyBy('name');
            $submittedLinks = collect($request->links)->keyBy('title');
            
            // Delete links that are no longer in the submitted list
            $linksToDelete = $existingLinks->keys()->diff($submittedLinks->keys());
            if ($linksToDelete->isNotEmpty()) {
                $profile->links()->whereIn('name', $linksToDelete)->delete();
            }
            
            // Update or create links
            foreach ($request->links as $linkData) {
                // Determine link type based on title
                $linkTypeKey = $this->determineLinkType($linkData['title']);
                $linkType = LinkType::where('key', $linkTypeKey)->first();
                
                if ($linkType) {
                    $existingLink = $existingLinks->get($linkData['title']);
                    
                    if ($existingLink) {
                        // Update existing link if changed
                        if ($existingLink->url !== $linkData['url'] || $existingLink->link_type_id !== $linkType->id) {
                            $existingLink->update([
                                'url' => $linkData['url'],
                                'link_type_id' => $linkType->id,
                                'key' => $linkType->key,
                            ]);
                        }
                    } else {
                        // Create new link
                        $link = Link::create([
                            'key' => $linkType->key,
                            'name' => $linkData['title'],
                            'url' => $linkData['url'],
                            'link_type_id' => $linkType->id,
                            'linkable_id' => $profile->id,
                            'linkable_type' => Profile::class,
                        ]);
                        

                    }
                }
            }
        }

        // Handle assets
        
        if ($request->hasFile('assets')) {
            $minioService = new MinIOService();
            $bucketName = $user->username;
            
            // Create profile folder inside user bucket
            $profileFolder = 'profile/';
            
            $assets = $request->file('assets');
            $displayNames = $request->input('asset_display_names', []);
            $docTypes = $request->input('asset_doc_types', []);
            
            foreach ($assets as $index => $file) {

                
                // Get display name and doc type
                $displayName = $displayNames[$index] ?? $file->getClientOriginalName();
                $docType = $docTypes[$index] ?? 'other';
                

                
                // Determine asset type based on doc type
                $assetTypeKey = $this->determineAssetTypeForProfile($file, $docType);
                

                
                $assetType = AssetType::where('key', $assetTypeKey)->first();
                

                
                if ($assetType) {
                    // Generate unique filename
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $filePath = $profileFolder . $filename;
                    

                    
                    // Upload to MinIO
                    $uploadResult = $minioService->uploadFile($file, $bucketName, $assetTypeKey, $filename);
                    

                    
                    if ($uploadResult['success']) {
                        $asset = Asset::create([
                            'display_name' => $displayName,
                            'filename' => $uploadResult['url'],
                            'asset_type_id' => $assetType->id,
                            'is_active' => true,
                            'assetable_id' => $profile->id,
                            'assetable_type' => Profile::class,
                        ]);
                        

                    }
                }
            }
        }

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    /**
     * Remove the user's profile photo.
     */
    public function removePhoto(Request $request)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Get the user's profile
        $profile = $user->profile;
        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        // Find the existing profile photo
        $existingProfilePhoto = $profile->assets()
            ->where('display_name', 'Profile Photo')
            ->whereHas('assetType', function($query) {
                $query->where('key', 'images');
            })
            ->first();

        if (!$existingProfilePhoto) {
            return response()->json(['success' => false, 'message' => 'No profile photo found'], 404);
        }

        try {
            $minioService = new MinIOService();
            $bucketName = $user->username;
            
            // Extract filename from the stored URL
            // The URL format is: http://localhost:4811/projectsdashboard/username/images/filename.jpg
            $urlParts = explode('/', $existingProfilePhoto->filename);
            $filename = end($urlParts); // Get the last part which is the actual filename
            

            
            // Delete from MinIO
            $deleteResult = $minioService->deleteFile($bucketName, 'images', $filename);
            
            if (!$deleteResult) {
                return response()->json(['success' => false, 'message' => 'Failed to delete file from storage'], 500);
            }
            
            // Soft delete from database
            $existingProfilePhoto->delete();
            

            
            return response()->json(['success' => true, 'message' => 'Profile photo removed successfully']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to remove profile photo'], 500);
        }
    }

    /**
     * Remove a specific asset from the user's profile.
     */
    public function removeAsset(Request $request, $assetId)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Get the user's profile
        $profile = $user->profile;
        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        // Find the specific asset
        $asset = $profile->assets()->find($assetId);
        
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
        }

        try {
            $minioService = new MinIOService();
            $bucketName = $user->username;
            
            // Extract filename from the stored URL
            // The URL format is: http://localhost:4811/projectsdashboard/username/assettype/filename.jpg
            $urlParts = explode('/', $asset->filename);
            $filename = end($urlParts); // Get the last part which is the actual filename
            
            // Get asset type for MinIO deletion
            $assetType = $asset->assetType ? $asset->assetType->key : 'documents';
            

            
            // Delete from MinIO
            $deleteResult = $minioService->deleteFile($bucketName, $assetType, $filename);
            
            if (!$deleteResult) {
                return response()->json(['success' => false, 'message' => 'Failed to delete file from storage'], 500);
            }
            
            // Soft delete from database
            $asset->delete();
            

            
            return response()->json(['success' => true, 'message' => 'Asset removed successfully']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to remove asset'], 500);
        }
    }

    /**
     * Set public URL for the user's profile
     */
    public function setPublicUrl(Request $request)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $request->validate([
            'public_url' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/'
        ], [
            'public_url.required' => 'Public URL is required',
            'public_url.max' => 'Public URL must be less than 50 characters',
            'public_url.regex' => 'Public URL can only contain letters, numbers, hyphens, and underscores'
        ]);

        $publicUrl = $request->input('public_url');
        
        // Check if this public URL already exists
        $existingProfile = Profile::where('public_url', $publicUrl)
            ->where('user_id', '!=', $user->id)
            ->first();
            
        if ($existingProfile) {
            return response()->json([
                'success' => false, 
                'message' => 'This URL is already taken. Please choose a different one.'
            ], 422);
        }

        // Get or create profile for the user
        $profile = $user->profile;
        if (!$profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;
        }

        $profile->public_url = $publicUrl;
        $profile->save();

        return response()->json([
            'success' => true, 
            'message' => 'Public URL set successfully',
            'public_url' => $publicUrl
        ]);
    }

    /**
     * Update public URL for the user's profile
     */
    public function updatePublicUrl(Request $request)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $request->validate([
            'public_url' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/'
        ], [
            'public_url.required' => 'Public URL is required',
            'public_url.max' => 'Public URL must be less than 50 characters',
            'public_url.regex' => 'Public URL can only contain letters, numbers, hyphens, and underscores'
        ]);

        $publicUrl = $request->input('public_url');
        
        // Get the user's profile
        $profile = $user->profile;
        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        // Check if the new URL is the same as the current one
        if ($profile->public_url === $publicUrl) {
            return response()->json([
                'success' => false, 
                'message' => 'This is already your current URL'
            ], 422);
        }
        
        // Check if this public URL already exists for another user
        $existingProfile = Profile::where('public_url', $publicUrl)
            ->where('user_id', '!=', $user->id)
            ->first();
            
        if ($existingProfile) {
            return response()->json([
                'success' => false, 
                'message' => 'This URL is already taken. Please choose a different one.'
            ], 422);
        }

        // Update the profile
        $profile->public_url = $publicUrl;
        $profile->save();

        return response()->json([
            'success' => true, 
            'message' => 'Public URL updated successfully',
            'public_url' => $publicUrl
        ]);
    }

    /**
     * Delete public URL for the user's profile
     */
    public function deletePublicUrl(Request $request)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Get the user's profile
        $profile = $user->profile;
        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        // Check if profile has a public URL to delete
        if (!$profile->public_url) {
            return response()->json([
                'success' => false, 
                'message' => 'No public URL to delete'
            ], 422);
        }

        // Set public_url to null and disable sharing
        $profile->public_url = null;
        $profile->share_profile = false;
        $profile->save();

        return response()->json([
            'success' => true, 
            'message' => 'Public URL deleted successfully'
        ]);
    }

    /**
     * Determine the appropriate link type based on the title
     */
    private function determineLinkType($title)
    {
        $title = strtolower($title);
        
        if (str_contains($title, 'linkedin')) {
            return 'linkedin';
        } elseif (str_contains($title, 'github')) {
            return 'github';
        } else {
            return 'portfolio';
        }
    }

    private function determineAssetTypeForProfile($file, $docType)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = strtolower($file->getMimeType());
        
        // For resume and cover letter, always use document type
        if ($docType === 'resume' || $docType === 'cover-letter') {
            return 'documents';
        }
        
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

    private function determineAssetType($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = strtolower($file->getMimeType());
        $filename = strtolower($file->getClientOriginalName());
        
        // Check for specific document types
        if (str_contains($filename, 'resume') || str_contains($filename, 'cv')) {
            return 'document';
        } elseif (str_contains($filename, 'cover') || str_contains($filename, 'letter')) {
            return 'document';
        }
        
        // Image files
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'tiff']) || 
            str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        // Video files
        if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v']) || 
            str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        // Document files (PDFs, Word docs, Excel, etc.)
        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md', 'rtf']) || 
            str_starts_with($mimeType, 'application/pdf') ||
            str_starts_with($mimeType, 'application/msword') ||
            str_starts_with($mimeType, 'application/vnd.openxmlformats-officedocument')) {
            return 'document';
        }
        
        // Default to others for everything else
        return 'others';
    }
}