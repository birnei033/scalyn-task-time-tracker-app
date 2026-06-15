<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Support\UserManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $users = User::with('team')
            ->withCount(['assignedTasks', 'timeEntries'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('team_id'), fn ($query) => $query->where('team_id', $request->integer('team_id')))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'teams' => Team::orderBy('name')->get(),
            'roles' => UserManagement::roles(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        return view('users.create', $this->formData(new User));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate(UserManagement::storeRules());

        User::create($validated);

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user->load('team')->loadCount(['assignedTasks', 'timeEntries']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('users.edit', $this->formData($user));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $validated = $request->validate(UserManagement::updateRules($user, $request->filled('password')));

        $user->fill($validated);

        $user->save();

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        abort_if(auth()->id() === $user->id, 403);

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User deleted.');
    }

    private function formData(User $user): array
    {
        return [
            'user' => $user,
            'teams' => Team::orderBy('name')->get(),
            'roles' => UserManagement::roles(),
        ];
    }
}
