<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => User::query()->latest()->paginate(20, ['id', 'name', 'email', 'role']),
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot change the role of the current account.');
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'manager', 'user'])],
        ]);
        $user->update($data);

        return response()->json(['data' => $user->only(['id', 'name', 'email', 'role'])]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot delete the current account.');
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
