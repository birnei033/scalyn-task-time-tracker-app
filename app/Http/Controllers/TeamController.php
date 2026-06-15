<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Support\UserManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Team::class);

        return view('team.index', [
            'teams' => Team::withCount('users')->orderBy('name')->get(),
            'users' => User::with('team')->orderBy('name')->get(),
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $data = $request->validate(UserManagement::assignmentRules());

        $user->update($data);

        return redirect()->route('team.index')->with('status', 'User updated.');
    }
}
