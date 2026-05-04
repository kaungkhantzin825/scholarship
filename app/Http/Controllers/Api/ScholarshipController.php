<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScholarshipResource;
use App\Http\Resources\ScholarshipDetailResource;
use App\Models\Scholarship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScholarshipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $scholarships = Scholarship::with(['category', 'tags'])
            ->active()
            ->filter($request->only([
                'search', 'country', 'level', 'category',
                'field', 'amount_type', 'deadline_from', 'deadline_to',
            ]))
            ->orderByDesc($request->get('sort', 'created_at'))
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data'  => ScholarshipResource::collection($scholarships),
            'meta'  => [
                'current_page' => $scholarships->currentPage(),
                'last_page'    => $scholarships->lastPage(),
                'per_page'     => $scholarships->perPage(),
                'total'        => $scholarships->total(),
            ],
        ]);
    }

    public function featured(): JsonResponse
    {
        $scholarships = Scholarship::with(['category', 'tags'])
            ->active()
            ->featured()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => ScholarshipResource::collection($scholarships),
        ]);
    }

    public function latest(): JsonResponse
    {
        $scholarships = Scholarship::with(['category'])
            ->active()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => ScholarshipResource::collection($scholarships),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $scholarship = Scholarship::with(['category', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        $scholarship->incrementViews();

        return response()->json([
            'data' => new ScholarshipDetailResource($scholarship),
        ]);
    }

    public function related(string $slug): JsonResponse
    {
        $scholarship = Scholarship::where('slug', $slug)->firstOrFail();

        $related = Scholarship::with(['category'])
            ->active()
            ->where('id', '!=', $scholarship->id)
            ->where(function ($q) use ($scholarship) {
                $q->where('category_id', $scholarship->category_id)
                  ->orWhere('country', $scholarship->country)
                  ->orWhere('level', $scholarship->level);
            })
            ->orderByDesc('is_featured')
            ->limit(6)
            ->get();

        return response()->json([
            'data' => ScholarshipResource::collection($related),
        ]);
    }
}
