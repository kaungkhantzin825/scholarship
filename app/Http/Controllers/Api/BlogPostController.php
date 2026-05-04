<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostResource;
use App\Http\Resources\BlogPostDetailResource;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::published()
            ->when($request->category, fn($q, $c) => $q->where('post_category', $c))
            ->when($request->search, fn($q, $s) =>
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('excerpt', 'like', "%{$s}%")
            )
            ->orderByDesc('published_at')
            ->paginate($request->get('per_page', 12));

        return response()->json([
            'data' => BlogPostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'per_page'     => $posts->perPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    public function featured(): JsonResponse
    {
        $posts = BlogPost::published()
            ->featured()
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => BlogPostResource::collection($posts),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $post->incrementViews();

        return response()->json([
            'data' => new BlogPostDetailResource($post),
        ]);
    }
}
