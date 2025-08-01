<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|string|in:planning,in_progress,review,completed,on_hold',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'budget' => 'nullable|string|max:255',
            'team_members' => 'nullable|array',
            'team_members.*' => 'string|max:255',
            'client' => 'nullable|string|max:255',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:255',
            'notes' => 'nullable|string',
            'links' => 'nullable|array',
            'links.*.title' => 'required|string|max:255',
            'links.*.url' => 'required|url|max:500',
            'assets' => 'nullable|array',
            'assets.*' => 'file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // For now, we'll just return a success response
        // In a real application, you would save this to a database
        // and handle file uploads to storage
        
        $data = $validator->validated();
        
        // Handle file uploads if any
        if ($request->hasFile('assets')) {
            $uploadedFiles = [];
            foreach ($request->file('assets') as $file) {
                $path = $file->store('project-assets', 'public');
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
            $data['assets'] = $uploadedFiles;
        }

        // Here you would typically save to database
        // For now, we'll just redirect with a success message
        
        return redirect()->route('home')->with('success', 'Project created successfully!');
    }
} 