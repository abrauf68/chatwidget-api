<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreAgentRequest;
use App\Http\Requests\Chat\UpdateAgentSitesRequest;
use App\Http\Resources\UserResource;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    /**
     * Lightweight, site-scoped agent list used to populate pickers like
     * the "transfer chat" dropdown. Unlike index() below, this does NOT
     * require `agents.manage`: any agent who can see the site (same rule
     * as the site itself) can see who else works that site, so they can
     * hand a conversation off to them.
     */
    public function forSite(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        // Site-pivot agents plus super admins (who can access every site,
        // and so are always valid transfer targets even without a pivot row).
        $agents = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where('status', 'active')
            ->where(function ($q) use ($site) {
                $q->whereHas('sites', fn ($sq) => $sq->where('sites.id', $site->id))
                    ->orWhereHas('roles', fn ($rq) => $rq->where('name', 'super_admin'));
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => UserResource::collection($agents)->collection,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('agents.manage', User::class);

        $agents = User::query()
            ->with(['roles', 'sites'])
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => UserResource::collection($agents)->collection,
            'meta' => [
                'current_page' => $agents->currentPage(),
                'per_page' => $agents->perPage(),
                'total' => $agents->total(),
            ],
        ]);
    }

    public function store(StoreAgentRequest $request): JsonResponse
    {
        $agent = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'status' => 'active',
        ]);

        $agent->assignRole($request->validated('role'));
        $agent->sites()->sync($request->validated('site_ids', []));

        return response()->json(['data' => new UserResource($agent->load('roles', 'sites'))], 201);
    }

    public function updateSites(UpdateAgentSitesRequest $request, User $agent): JsonResponse
    {
        $agent->sites()->sync($request->validated('site_ids'));

        return response()->json(['data' => new UserResource($agent->load('sites'))]);
    }
}
