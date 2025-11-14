<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content\Content;
use App\Models\Content\ContentAnalytics;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    /**
     * Display a listing of the content.
     */
    public function index(Request $request): JsonResponse
    {
        $contents = Content::with(['author', 'analytics', 'seo'])
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->featured, function ($query, $featured) {
                return $query->where('is_featured', $featured);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    return $q->where('title', 'like', "%{$search}%")
                           ->orWhere('content', 'like', "%{$search}%")
                           ->orWhere('meta_description', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->successResponse($contents);
    }

    /**
     * Store a newly created content.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:contents,slug',
            'content' => 'required|string',
            'type' => 'required|in:' . implode(',', [
                Content::TYPE_PAGE,
                Content::TYPE_BLOG_POST,
                Content::TYPE_PRODUCT,
                Content::TYPE_CATEGORY,
                Content::TYPE_NEWS,
                Content::TYPE_FAQ,
                Content::TYPE_TESTIMONIAL,
                Content::TYPE_LANDING_PAGE,
                Content::TYPE_BANNER,
                Content::TYPE_POPUP,
                Content::TYPE_FORM,
                Content::TYPE_WIDGET,
            ]),
            'excerpt' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|max:2048',
            'published_at' => 'nullable|date',
            'author_id' => 'required|exists:users,id',
            'parent_id' => 'nullable|exists:contents,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'password_protected' => 'boolean',
            'view_count' => 'integer|min:0',
            'like_count' => 'integer|min:0',
            'share_count' => 'integer|min:0',
            'seo_score' => 'numeric|min:0|max:100',
            'reading_time' => 'integer|min:1|max:30',
            'schema_data' => 'nullable|array',
            'status' => 'required|in:' . implode(',', [
                Content::STATUS_DRAFT,
                Content::STATUS_REVIEW,
                Content::STATUS_PUBLISHED,
                Content::STATUS_SCHEDULED,
                Content::STATUS_ARCHIVED,
            ]),
        ]);

        $content = Content::create($validated);

        // Update SEO score
        $content->updateSeoScore();

        return $this->successResponse($content, 'Content created successfully', 201);
    }

    /**
     * Display the specified content.
     */
    public function show(Content $content): JsonResponse
    {
        $content->load(['author', 'analytics', 'seo', 'media', 'tags']);

        // Track page view
        if ($content->analytics) {
            $content->analytics->trackPageView();
        }

        return $this->successResponse($content);
    }

    /**
     * Update the specified content.
     */
    public function update(Request $request, Content $content): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:contents,slug,' . $content->id,
            'content' => 'sometimes|string',
            'excerpt' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|max:2048',
            'published_at' => 'nullable|date',
            'parent_id' => 'nullable|exists:contents,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'password_protected' => 'boolean',
            'view_count' => 'integer|min:0',
            'like_count' => 'integer|min:0',
            'share_count' => 'integer|min:0',
            'seo_score' => 'numeric|min:0|max:100',
            'reading_time' => 'integer|min:1|max:30',
            'schema_data' => 'nullable|array',
            'status' => 'required|in:' . implode(',', [
                Content::STATUS_DRAFT,
                Content::STATUS_REVIEW,
                Content::STATUS_PUBLISHED,
                Content::STATUS_SCHEDULED,
                Content::STATUS_ARCHIVED,
            ]),
        ]);

        $content->update($validated);

        // Update SEO score
        $content->updateSeoScore();

        return $this->successResponse($content, 'Content updated successfully');
    }

    /**
     * Remove the specified content.
     */
    public function destroy(Content $content): JsonResponse
    {
        $content->delete();

        return $this->successResponse(null, 'Content deleted successfully');
    }

    /**
     * Publish the specified content.
     */
    public function publish(Content $content): JsonResponse
    {
        $content->update([
            'status' => Content::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return $this->successResponse($content, 'Content published successfully');
    }

    /**
     * Archive the specified content.
     */
    public function archive(Content $content): JsonResponse
    {
        $content->update([
            'status' => Content::STATUS_ARCHIVED,
        ]);

        return $this->successResponse($content, 'Content archived successfully');
    }

    /**
     * Get content analytics.
     */
    public function analytics(Content $content): JsonResponse
    {
        $analytics = $content->analytics;

        if (!$analytics) {
            $analytics = $content->analytics()->create();
        }

        return $this->successResponse($analytics);
    }

    /**
     * Get content SEO data.
     */
    public function seo(Content $content): JsonResponse
    {
        $seo = $content->seo;

        if (!$seo) {
            $seo = $content->seo()->create();
        }

        return $this->successResponse($seo);
    }

    /**
     * Upload media for content.
     */
    public function uploadMedia(Request $request, Content $content): JsonResponse
    {
        $validated = $request->validate([
            'media' => 'required|array|max:10',
            'media.*' => 'required|file|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        foreach ($validated['media'] as $media) {
            $content->addMedia($media);
        }

        return $this->successResponse($content, 'Media uploaded successfully');
    }

    /**
     * Get content comments.
     */
    public function comments(Content $content): JsonResponse
    {
        $comments = $content->comments()->with(['user', 'reports'])->paginate(20);

        return $this->successResponse($comments);
    }

    /**
     * Add comment to content.
     */
    public function addComment(Request $request, Content $content): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:content_comments,id',
            'status' => 'required|in:' . implode(',', [
                ContentComment::STATUS_DRAFT,
                ContentComment::STATUS_REVIEW,
                ContentComment::STATUS_PUBLISHED,
            ]),
        ]);

        $comment = $content->comments()->create($validated);

        return $this->successResponse($comment, 'Comment added successfully', 201);
    }

    /**
     * Get content goals.
     */
    public function goals(Content $content): JsonResponse
    {
        $goals = $content->goals()->with('analytics')->get();

        return $this->successResponse($goals);
    }

    /**
     * Create content goal.
     */
    public function createGoal(Request $request, Content $content): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:' . implode(',', [
                ContentGoal::TYPE_REVENUE,
                ContentGoal::TYPE_CONVERSION,
                ContentGoal::TYPE_ENGAGEMENT,
                ContentGoal::TYPE_TRAFFIC,
                ContentGoal::TYPE_LEAD_GENERATION,
                ContentGoal::TYPE_USER_REGISTRATION,
                ContentGoal::TYPE_CONTENT_CONSUMPTION,
                ContentGoal::TYPE_SOCIAL_SHARING,
                ContentGoal::TYPE_EMAIL_SUBSCRIPTION,
            ]),
            'target_value' => 'required|numeric|min:0',
            'target_date' => 'required|date|after:today',
            'priority' => 'required|integer|min:1|max:4',
            'category' => 'required|in:' . implode(',', [
                ContentGoal::CATEGORY_BUSINESS,
                ContentGoal::CATEGORY_MARKETING,
                ContentGoal::CATEGORY_SALES,
                ContentGoal::CATEGORY_USER_EXPERIENCE,
                ContentGoal::CATEGORY_CONTENT,
                ContentGoal::CATEGORY_TECHNICAL,
            ]),
        ]);

        $goal = $content->goals()->create($validated);

        return $this->successResponse($goal, 'Goal created successfully', 201);
    }
}
