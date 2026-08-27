<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWidgetSite
{
    /**
     * Resolves the `site_key` from the request into a Site model bound as
     * `request()->attributes->get('site')`, and rejects requests coming
     * from a domain that isn't the site's allowed_domain. This is what
     * stops someone copying another site's widget snippet onto their own
     * page (architecture doc section 4).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $siteKey = $request->input('site_key') ?? $request->query('site_key');

        $site = Site::where('site_key', $siteKey)->first();

        if (! $site || ! $site->isActive()) {
            return response()->json(['message' => 'Invalid site key.'], 404);
        }

        $origin = $request->headers->get('origin') ?? $request->headers->get('referer');

        if (app()->environment('production') && $origin) {
            $originHost = parse_url($origin, PHP_URL_HOST);

            if ($originHost && ! str_ends_with($originHost, $site->allowed_domain)) {
                return response()->json(['message' => 'Origin not allowed for this site.'], 403);
            }
        }

        $request->attributes->set('site', $site);

        return $next($request);
    }
}
