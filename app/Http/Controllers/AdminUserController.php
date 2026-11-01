<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()->latest()->paginate(15),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot change your own role.');
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'manager', 'user'])],
        ]);
        $user->update($data);

        return back()->with('success', 'User role updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');
        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
