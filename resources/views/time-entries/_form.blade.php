@php($timeEntry = $entry ?? new \App\Models\TimeEntry)
@include('time-entries._fields', ['timeEntry' => $timeEntry, 'contextTask' => $contextTask ?? null, 'showUserSelect' => auth()->user()->canManageTeam()])
