<x-app-layout>
    <x-slot name="header">{{ $task->title }}</x-slot>

    <section class="page-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="page-kicker mb-2">Task details</div>
                <h2 class="page-title h1 mb-3">{{ $task->title }}</h2>
                <p class="page-subtitle mb-0">
                    Review the task scope, progress history, and logged hours in a cleaner layout.
                </p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-lg-end gap-3">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    @can('update', $task)
                        <a class="btn btn-primary" href="{{ route('tasks.edit', $task) }}">
                            <i class="bi bi-pencil me-1"></i> Edit Task
                        </a>
                    @endcan
                    <a class="btn btn-outline-secondary" href="{{ route('tasks.index') }}">
                        Back
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('tasks._details')
    @include('tasks._log-time-modal', [
        'selectedTask' => $task,
        'showModal' => false,
    ])
</x-app-layout>
