<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreAgentRequest;
use App\Http\Requests\Chat\UpdateAgentSitesRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('agents.manage', User::class);

        $query = User::query()->with(['roles', 'sites'])->orderBy('name');

        // Used by the dashboard's "Transfer chat" picker to only list
        // agents who actually have access to the chat's site.
        if ($siteId = $request->query('site_id')) {
            $query->whereHas('sites', fn ($q) => $q->where('sites.id', $siteId))
                ->orWhereHas('roles', fn ($q) => $q->where('name', 'super_admin'));
        }

        $agents = $query->paginate($request->integer('per_page', 50));

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
