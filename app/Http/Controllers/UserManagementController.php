<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

final class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->orderBy('name')->paginate(20),
            'roles' => UserRole::cases(),
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = $request->validated('role');
        if ($request->user()->is($user) && $role !== UserRole::Admin->value) {
            throw ValidationException::withMessages([
                'role' => 'Administrator tidak dapat menurunkan perannya sendiri.',
            ]);
        }

        $user->update(['role' => $role]);

        return Redirect::route('users.index')->with('status', 'Peran pengguna berhasil diperbarui.');
    }
}
