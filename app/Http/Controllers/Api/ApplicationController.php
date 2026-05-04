<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Scholarship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $applications = $request->user()
            ->applications()
            ->with(['scholarship.category'])
            ->orderByDesc('applied_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => ApplicationResource::collection($applications),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page'    => $applications->lastPage(),
                'per_page'     => $applications->perPage(),
                'total'        => $applications->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scholarship_id' => 'required|exists:scholarships,id',
            'cover_letter'   => 'nullable|string|max:5000',
            'additional_info'=> 'nullable|array',
            'cv'             => 'nullable|file|mimes:pdf|max:5120',
        ]);

        // Check the scholarship is still active
        $scholarship = Scholarship::findOrFail($validated['scholarship_id']);

        if ($scholarship->status !== 'active') {
            return response()->json([
                'message' => 'This scholarship is no longer accepting applications.',
            ], 422);
        }

        // Prevent duplicate applications
        $exists = Application::where('user_id', $request->user()->id)
            ->where('scholarship_id', $validated['scholarship_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You have already applied for this scholarship.',
            ], 422);
        }

        $application = Application::create([
            'user_id'         => $request->user()->id,
            'scholarship_id'  => $validated['scholarship_id'],
            'cover_letter'    => $validated['cover_letter'] ?? null,
            'additional_info' => $validated['additional_info'] ?? null,
            'status'          => 'pending',
        ]);

        if ($request->hasFile('cv')) {
            $application->addMediaFromRequest('cv')->toMediaCollection('cv');
        }

        // Increment application counter on scholarship
        $scholarship->increment('applications_count');

        return response()->json([
            'message' => 'Application submitted successfully.',
            'data'    => new ApplicationResource($application->load('scholarship.category')),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $application = $request->user()
            ->applications()
            ->with(['scholarship.category'])
            ->findOrFail($id);

        return response()->json([
            'data' => new ApplicationResource($application),
        ]);
    }
}
