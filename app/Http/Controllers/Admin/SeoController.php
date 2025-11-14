<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    /**
     * Get SEO dashboard.
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_content' => Content::count(),
            'published_content' => Content::where('status', Content::STATUS_PUBLISHED)->count(),
            'draft_content' => Content::where('status', Content::STATUS_DRAFT)->count(),
            'avg_seo_score' => Content::avg('seo_score'),
            'high_seo_content' => Content::where('seo_score', '>', 80)->count(),
            'low_seo_content' => Content::where('seo_score', '<', 50)->count(),
            'content_needing_review' => Content::where('seo_score', '<', 60)->count(),
        ];

        return $this->successResponse($stats);
    }

    /**
     * Get content needing SEO review.
     */
    public function reviewQueue(): JsonResponse
    {
        $contents = Content::where('seo_score', '<', 60)
            ->with(['author', 'analytics'])
            ->orderBy('seo_score', 'asc')
            ->paginate(50);

        return $this->successResponse($contents);
    }

    /**
     * Generate sitemap.
     */
    public function sitemap(): JsonResponse
    {
        $contents = Content::where('status', Content::STATUS_PUBLISHED)
            ->where('type', Content::TYPE_PAGE)
            ->get(['id', 'slug', 'updated_at']);

        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($contents as $content) {
            $url = url('/content/' . $content->slug);
            $lastmod = $content->updated_at->format('Y-m-d\TH:i:sP');

            $sitemap .= '  <url>' . "\n";
            $sitemap .= '    <loc>' . $url . '</loc>' . "\n";
            $sitemap .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            $sitemap .= '    <changefreq>weekly</changefreq>' . "\n";
            $sitemap .= '    <priority>0.5</priority>' . "\n";
            $sitemap .= '  </url>' . "\n";
        }

        $sitemap .= '</urlset>' . "\n";

        return response($sitemap, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        'Cache-Control' => 'public, max-age=3600',
        'Content-Disposition' => 'attachment; filename="sitemap.xml"',
        'Content-Length' => strlen($sitemap),
        'ETag' => md5($sitemap),
        'Last-Modified' => now()->format('D, d M Y H:i:s \e'),
        ]);
    }

    /**
     * Generate robots.txt.
     */
    public function robots(): JsonResponse
    {
        $robots = "User-agent: *\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /cart/\n";
        $robots .= "Disallow: /checkout/\n";
        $robots .= "Disallow: /profile/\n";
        $robots .= "Allow: /\n";
        $robots .= "Allow: /content/\n";
        $robots .= "Sitemap: " . url('/sitemap') . "\n";

        return response($robots, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
            'Content-Length' => strlen($robots),
        ]);
    }

    /**
     * Get SEO analytics.
     */
    public function analytics(): JsonResponse
    {
        $analytics = [
            'total_pages' => Content::where('type', Content::TYPE_PAGE)->count(),
            'total_blog_posts' => Content::where('type', Content::TYPE_BLOG_POST)->count(),
            'total_products' => Content::where('type', Content::TYPE_PRODUCT)->count(),
            'avg_seo_score' => Content::avg('seo_score'),
            'content_by_type' => Content::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'top_performing_pages' => Content::where('type', Content::TYPE_PAGE)
                ->where('status', Content::STATUS_PUBLISHED)
                ->orderBy('view_count', 'desc')
                ->limit(10)
                ->get(['title', 'slug', 'view_count', 'seo_score']),
            'recent_content' => Content::where('status', Content::STATUS_PUBLISHED)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(['title', 'slug', 'updated_at', 'seo_score']),
        ];

        return $this->successResponse($analytics);
    }

    /**
     * Update SEO settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auto_generate_meta' => 'boolean',
            'default_meta_title' => 'nullable|string|max:60',
            'default_meta_description' => 'nullable|string|max:160',
            'default_meta_keywords' => 'nullable|string|max:255',
            'auto_generate_sitemap' => 'boolean',
            'enable_robots_txt' => 'boolean',
            'seo_score_threshold' => 'integer|min:0|max:100',
        ]);

        // Update settings in cache
        cache()->put('seo_settings', $validated, 3600);

        return $this->successResponse(null, 'SEO settings updated successfully');
    }

    /**
     * Bulk update SEO scores.
     */
    public function bulkUpdateSeoScores(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_ids' => 'required|array',
            'seo_score' => 'required|integer|min:0|max:100',
        ]);

        $contents = Content::whereIn('id', $validated['content_ids'])->get();

        foreach ($contents as $content) {
            $content->update(['seo_score' => $validated['seo_score']]);
        }

        return $this->successResponse(null, 'SEO scores updated for ' . count($contents) . ' content items');
    }
}
