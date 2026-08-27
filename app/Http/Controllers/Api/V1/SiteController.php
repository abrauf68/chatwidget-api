<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Chat\CreateSite;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreSiteRequest;
use App\Http\Requests\Site\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Site::query()->withCount('chatSessions');

        if (! $user->isSuperAdmin()) {
            $query->whereIn('id', $user->sites()->pluck('sites.id'));
        }

        $sites = $query->orderBy('name')->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => SiteResource::collection($sites)->collection,
            'meta' => [
                'current_page' => $sites->currentPage(),
                'per_page' => $sites->perPage(),
                'total' => $sites->total(),
            ],
        ]);
    }

    public function store(StoreSiteRequest $request, CreateSite $action): JsonResponse
    {
        $site = $action->handle($request->validated());

        return response()->json(['data' => new SiteResource($site)], 201);
    }

    public function show(Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        return response()->json(['data' => new SiteResource($site)]);
    }

    public function update(UpdateSiteRequest $request, Site $site): JsonResponse
    {
        $site->update($request->validated());

        return response()->json(['data' => new SiteResource($site)]);
    }

    public function destroy(Site $site): JsonResponse
    {
        $this->authorize('delete', $site);

        $site->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
