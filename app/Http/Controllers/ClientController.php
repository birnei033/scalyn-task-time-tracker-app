<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\TimeDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Client::class);

        $clients = $this->clientList(Client::active());

        return view('clients.index', compact('clients'));
    }

    public function archives()
    {
        Gate::authorize('viewAny', Client::class);

        $clients = $this->clientList(Client::archived());

        return view('clients.archives', compact('clients'));
    }

    public function create()
    {
        Gate::authorize('create', Client::class);

        return view('clients.create', ['client' => new Client]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Client::class);

        Client::create($this->validateClient($request));

        return redirect()->route('clients.index')->with('status', 'Client added.');
    }

    public function show(Client $client)
    {
        Gate::authorize('view', $client);

        $now = now();
        $client->load(['tasks.assignedUser', 'tasks.timeEntries']);
        $monthlyHours = $client->timeEntries()
            ->whereDate('date', '>=', $now->copy()->startOfMonth())
            ->whereDate('date', '<=', $now->copy()->endOfMonth())
            ->sum('hours');
        $monthlyMinutes = TimeDisplay::hoursToMinutes($monthlyHours);
        $monthlyExcessMinutes = $client->budget_per_month !== null && $monthlyMinutes > $client->budget_per_month
            ? $monthlyMinutes - $client->budget_per_month
            : null;

        return view('clients.show', compact('client', 'monthlyHours', 'monthlyExcessMinutes'));
    }

    public function edit(Client $client)
    {
        Gate::authorize('update', $client);

        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $client->update($this->validateClient($request));

        return redirect()->route('clients.index')->with('status', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        Gate::authorize('delete', $client);

        $client->archive();

        return redirect()->route('clients.index')->with('status', 'Client archived.');
    }

    public function restore(Client $client)
    {
        Gate::authorize('restore', $client);

        $client->unarchive();

        return redirect()->route('clients.index')->with('status', 'Client restored.');
    }

    public function forceDelete(Client $client)
    {
        Gate::authorize('forceDelete', $client);

        $client->delete();

        return redirect()->route('clients.archives')->with('status', 'Archived client deleted permanently.');
    }

    private function validateClient(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'budget_per_month' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,archived'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function clientList(Builder $query)
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        return $query
            ->when(request('search'), fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            }))
            ->withSum([
                'timeEntries as monthly_hours' => function (Builder $query) use ($startOfMonth, $endOfMonth) {
                    $query->whereDate('date', '>=', $startOfMonth)
                        ->whereDate('date', '<=', $endOfMonth);
                },
            ], 'hours')
            ->latest()
            ->paginate(12)
            ->withQueryString();
    }
}
