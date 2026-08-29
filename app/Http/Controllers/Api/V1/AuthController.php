<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Always returns a token alongside the SPA cookie session, per the
     * "client-agnostic auth" guideline (architecture doc section 17.1) —
     * a future mobile app can call this exact endpoint in token mode.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['This account has been suspended.'],
            ]);
        }

        $token = $user->createToken('agent-dashboard')->plainTextToken;

        Auth::login($user);

        return response()->json([
            'data' => [
                'user' => new UserResource($user->load('roles', 'sites')),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        Auth::guard('web')->logout();

        return response()->json(['data' => ['logged_out' => true]]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('roles', 'sites')),
        ]);
    }

    /**
     * Self-service profile edit (Settings page) — name only. Email is left
     * out on purpose: it's the login identifier and changing it is an
     * admin-managed action (agents.manage), not a personal setting.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json(['data' => new UserResource($user->load('roles', 'sites'))]);
    }

    /**
     * Self-service password change — requires the current password, unlike
     * an admin-triggered reset, since the agent is already signed in and
     * this isn't a "forgot password" recovery flow.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! \Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($validated['password'])]);

        return response()->json(['data' => ['updated' => true]]);
    }
}
