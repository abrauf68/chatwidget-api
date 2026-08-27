<?php

namespace App\Http\Controllers\Api\V1\Widget;

use App\Http\Controllers\Controller;
use App\Http\Resources\WidgetConfigResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetConfigController extends Controller
{
    /**
     * GET /api/v1/widget/config?site_key=...
     * Site is already resolved + domain-verified by VerifyWidgetSite.
     */
    public function show(Request $request): JsonResponse
    {
        $site = $request->attributes->get('site');

        return response()->json(['data' => new WidgetConfigResource($site)]);
    }
}
