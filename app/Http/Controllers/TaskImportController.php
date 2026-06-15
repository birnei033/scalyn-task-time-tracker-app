<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\TaskActivityEntry;
use App\Models\User;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskImportController extends Controller
{
    private const IMPORT_DIR = 'task-imports';

    private const MAPPING_FIELDS = [
        'client_id' => ['label' => 'Client', 'required' => true],
        'assigned_user_id' => ['label' => 'Assigned User', 'required' => false],
        'title' => ['label' => 'Task Title', 'required' => true],
        'description' => ['label' => 'Description', 'required' => false],
        'status' => ['label' => 'Status', 'required' => false],
        'priority' => ['label' => 'Priority', 'required' => false],
    ];

    public function create()
    {
        Gate::authorize('create', Task::class);

        return view('tasks.import-upload');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Task::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'extensions:csv'],
        ]);

        $path = $validated['file']->storeAs(self::IMPORT_DIR, Str::uuid().'.csv');
        $preview = $this->readCsvPreview($path);

        if ($preview['headers'] === []) {
            Storage::disk('local')->delete($path);

            throw ValidationException::withMessages([
                'file' => 'The CSV file must contain a header row.',
            ]);
        }

        return redirect()->route('tasks.import.show', $this->importTokenFromPath($path));
    }

    public function show(string $importToken)
    {
        Gate::authorize('create', Task::class);

        $path = $this->importPath($importToken);

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $preview = $this->readCsvPreview($path);

        if ($preview['headers'] === []) {
            abort(404);
        }

        return view('tasks.import-map', [
            'importToken' => $importToken,
            'headers' => $preview['headers'],
            'sampleRows' => $preview['rows'],
            'fields' => self::MAPPING_FIELDS,
            'defaultMapping' => $this->defaultMapping($preview['headers']),
        ]);
    }

    public function process(Request $request, string $importToken)
    {
        Gate::authorize('create', Task::class);

        $path = $this->importPath($importToken);

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $preview = $this->readCsvPreview($path);
        $headers = $preview['headers'];
        $headerIndex = array_flip($headers);
        $mapping = $this->validateMapping($request, $headers);
        $rows = $this->readCsvRows($path);

        $results = [];
        $createdTasks = [];
        $createdCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($rows['rows'] as $rowNumber => $row) {
            if ($this->rowIsBlank($row)) {
                $skippedCount++;
                continue;
            }

            $resolved = $this->resolveMappedRow($row, $headerIndex, $mapping);

            if ($resolved['errors'] !== []) {
                $failedCount++;
                $results[] = [
                    'row' => $rowNumber,
                    'status' => 'failed',
                    'messages' => $resolved['errors'],
                ];

                continue;
            }

            try {
                $task = DB::transaction(function () use ($resolved, $request) {
                    $task = Task::create([
                        'client_id' => $resolved['data']['client_id'],
                        'assigned_user_id' => $resolved['data']['assigned_user_id'],
                        'title' => $resolved['data']['title'],
                        'description' => RichText::clean($resolved['data']['description']),
                        'status' => $resolved['data']['status'],
                        'priority' => $resolved['data']['priority'],
                    ]);

                    $this->recordTaskActivity($task, $request->user(), 'created');

                    return $task;
                });

                $createdCount++;
                $createdTasks[] = $task;
                $results[] = [
                    'row' => $rowNumber,
                    'status' => 'created',
                    'messages' => ["Created task '{$task->title}'."],
                ];
            } catch (\Throwable $e) {
                report($e);

                $failedCount++;
                $results[] = [
                    'row' => $rowNumber,
                    'status' => 'failed',
                    'messages' => ['The task could not be created.'],
                ];
            }
        }

        Storage::disk('local')->delete($path);

        return view('tasks.import-result', [
            'createdCount' => $createdCount,
            'failedCount' => $failedCount,
            'skippedCount' => $skippedCount,
            'results' => $results,
            'createdTasks' => $createdTasks,
        ]);
    }

    private function importTokenFromPath(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    private function importPath(string $importToken): string
    {
        return self::IMPORT_DIR.'/'.$importToken.'.csv';
    }

    private function validateMapping(Request $request, array $headers): array
    {
        $rules = [];

        foreach (self::MAPPING_FIELDS as $field => $definition) {
            $rules['mapping.'.$field] = [
                $definition['required'] ? 'required' : 'nullable',
                'string',
                Rule::in($headers),
            ];
        }

        $validated = $request->validate($rules);
        $mapping = $validated['mapping'] ?? [];

        foreach (array_keys(self::MAPPING_FIELDS) as $field) {
            $mapping[$field] = $mapping[$field] ?? null;
        }

        return $mapping;
    }

    private function readCsvPreview(string $path): array
    {
        $rows = $this->readCsvRows($path, 5);

        return [
            'headers' => $rows['headers'],
            'rows' => $rows['rows'],
        ];
    }

    private function readCsvRows(string $path, ?int $limit = null): array
    {
        $absolutePath = Storage::disk('local')->path($path);
        $handle = fopen($absolutePath, 'r');

        if ($handle === false) {
            return ['headers' => [], 'rows' => []];
        }

        $delimiter = $this->detectDelimiter($absolutePath);
        $headers = [];
        $rows = [];
        $rowNumber = 1;

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line = $this->normalizeCsvLine($line);

            if ($line === []) {
                $rowNumber++;
                continue;
            }

            if ($headers === []) {
                $headers = array_map(fn ($value) => $this->cleanCsvValue($value), $line);
                $rowNumber++;
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $this->cleanCsvValue($line[$index] ?? null);
            }

            if ($limit === null || count($rows) < $limit) {
                $rows[$rowNumber] = $row;
            }

            $rowNumber++;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    private function detectDelimiter(string $absolutePath): string
    {
        $sample = (string) file_get_contents($absolutePath, false, null, 0, 4096);
        $candidates = [
            ',' => substr_count($sample, ','),
            ';' => substr_count($sample, ';'),
            "\t" => substr_count($sample, "\t"),
            '|' => substr_count($sample, '|'),
        ];

        arsort($candidates);

        return array_key_first($candidates) ?: ',';
    }

    private function normalizeCsvLine(array $line): array
    {
        if ($line === []) {
            return [];
        }

        if (isset($line[0])) {
            $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $line[0]);
        }

        return array_map(fn ($value) => $value === null ? null : (string) $value, $line);
    }

    private function cleanCsvValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    private function resolveMappedRow(array $row, array $headerIndex, array $mapping): array
    {
        $errors = [];
        $data = [
            'client_id' => null,
            'assigned_user_id' => null,
            'title' => null,
            'description' => null,
            'status' => 'open',
            'priority' => 'medium',
        ];

        $clientName = $this->mappedValue($row, $headerIndex, $mapping['client_id'] ?? null);
        $title = $this->mappedValue($row, $headerIndex, $mapping['title'] ?? null);

        if ($clientName === null) {
            $errors[] = 'Client is required.';
        } else {
            $client = $this->resolveClient($clientName);

            if ($client === null) {
                $errors[] = "Client '{$clientName}' was not found.";
            } else {
                $data['client_id'] = $client->id;
            }
        }

        if ($title === null) {
            $errors[] = 'Task title is required.';
        } else {
            $data['title'] = $title;
        }

        $assignedUserName = $this->mappedValue($row, $headerIndex, $mapping['assigned_user_id'] ?? null);

        if ($assignedUserName !== null) {
            $user = $this->resolveUser($assignedUserName);

            if ($user === null) {
                $errors[] = "Assigned user '{$assignedUserName}' was not found.";
            } else {
                $data['assigned_user_id'] = $user->id;
            }
        }

        $data['description'] = $this->mappedValue($row, $headerIndex, $mapping['description'] ?? null);

        $status = $this->mappedValue($row, $headerIndex, $mapping['status'] ?? null);
        if ($status !== null) {
            $normalizedStatus = $this->normalizeTaskChoice($status);

            if (! in_array($normalizedStatus, Task::statusValues(), true)) {
                $errors[] = "Status '{$status}' is not valid.";
            } else {
                $data['status'] = $normalizedStatus;
            }
        }

        $priority = $this->mappedValue($row, $headerIndex, $mapping['priority'] ?? null);
        if ($priority !== null) {
            $normalizedPriority = $this->normalizeTaskChoice($priority);

            if (! in_array($normalizedPriority, ['low', 'medium', 'high'], true)) {
                $errors[] = "Priority '{$priority}' is not valid.";
            } else {
                $data['priority'] = $normalizedPriority;
            }
        }

        return [
            'data' => $data,
            'errors' => $errors,
        ];
    }

    private function mappedValue(array $row, array $headerIndex, ?string $header): ?string
    {
        if ($header === null || ! array_key_exists($header, $headerIndex)) {
            return null;
        }

        return $this->cleanCsvValue($row[$header] ?? null);
    }

    private function resolveClient(string $name): ?Client
    {
        $matches = Client::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function resolveUser(string $name): ?User
    {
        $matches = User::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function normalizeTaskChoice(string $value): string
    {
        return Str::of($value)
            ->trim()
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();
    }

    private function defaultMapping(array $headers): array
    {
        $normalizedHeaders = [];

        foreach ($headers as $header) {
            $normalizedHeaders[$this->normalizeTaskChoice($header)] = $header;
        }

        $defaults = [
            'client_id' => ['client', 'client name'],
            'assigned_user_id' => ['assigned_user', 'assigned user', 'assignee'],
            'title' => ['title', 'task title', 'task'],
            'description' => ['description', 'notes'],
            'status' => ['status', 'state'],
            'priority' => ['priority', 'importance'],
        ];

        $mapping = [];

        foreach ($defaults as $field => $candidates) {
            $mapping[$field] = null;

            foreach ($candidates as $candidate) {
                $normalizedCandidate = $this->normalizeTaskChoice($candidate);

                if (isset($normalizedHeaders[$normalizedCandidate])) {
                    $mapping[$field] = $normalizedHeaders[$normalizedCandidate];
                    break;
                }
            }
        }

        return $mapping;
    }

    private function recordTaskActivity(Task $task, ?User $user, string $action): void
    {
        if (! Schema::hasTable('task_activity_entries')) {
            return;
        }

        TaskActivityEntry::create([
            'task_id' => $task->id,
            'user_id' => $user?->id,
            'action' => $action,
        ]);
    }
}
