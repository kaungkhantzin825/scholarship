<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ScholarshipResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->withCount(['activeScholarships as scholarships_count'])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function scholarships(string $slug, Request $request): JsonResponse
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $scholarships = $category->scholarships()
            ->with(['category', 'tags'])
            ->active()
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'category' => new CategoryResource($category),
            'data'     => ScholarshipResource::collection($scholarships),
            'meta'     => [
                'current_page' => $scholarships->currentPage(),
                'last_page'    => $scholarships->lastPage(),
                'per_page'     => $scholarships->perPage(),
                'total'        => $scholarships->total(),
            ],
        ]);
    }
}
