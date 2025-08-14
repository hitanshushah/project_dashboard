<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->attributes->get('user')?->only(['id', 'username', 'email']),
                'profile' => $this->getProfileData($request->attributes->get('user')),
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Get profile data including profile photo, links, and documents
     */
    private function getProfileData($user)
    {
        if (!$user || !$user->profile) {
            return null;
        }

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
                    'url' => $asset->filename, // MinIO URL
                    'type' => $asset->assetType ? $asset->assetType->key : 'documents',
                ];
            })
            ->toArray();

        $profileData = [
            'id' => $profile->id,
            'name' => $profile->name,
            'bio' => $profile->bio,
            'profile_photo_url' => $profilePhoto ? $profilePhoto->filename : null,
            'links' => $links,
            'documents' => $documents,
        ];

        \Log::info('Profile data for navbar:', [
            'user_id' => $user->id,
            'profile_photo_found' => $profilePhoto ? true : false,
            'profile_photo_url' => $profilePhoto ? $profilePhoto->filename : null,
            'links_count' => count($links),
            'documents_count' => count($documents),
            'profile_data' => $profileData
        ]);

        return $profileData;
    }
}
