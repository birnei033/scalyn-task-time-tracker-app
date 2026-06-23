<?php

namespace App\Support;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskAttachmentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class TaskAttachmentService
{
    private const MAX_SIZE_KB = 20480;

    private const MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/tiff',
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/rtf',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
    ];

    public static function taskAttachmentRules(string $field = 'attachments'): array
    {
        return [
            $field => ['nullable', 'array', 'max:10'],
            $field.'.*' => self::fileRules(),
        ];
    }

    public static function fileRules(): array
    {
        return [
            'file',
            'max:'.self::MAX_SIZE_KB,
            'mimetypes:'.implode(',', self::MIME_TYPES),
        ];
    }

    public static function singleFileRules(string $field = 'attachment'): array
    {
        return [
            $field => array_merge(['required'], self::fileRules()),
        ];
    }

    public function create(Task $task, UploadedFile $file, ?User $user): TaskAttachment
    {
        return DB::transaction(function () use ($task, $file, $user) {
            $attachment = TaskAttachment::create($this->buildAttachmentData($task, $file, $user));
            $this->snapshot($attachment, 'created', $user);

            return $attachment->fresh(['uploader', 'versions.actor']);
        });
    }

    public function replace(TaskAttachment $attachment, UploadedFile $file, ?User $user): TaskAttachment
    {
        return DB::transaction(function () use ($attachment, $file, $user) {
            $attachment->loadMissing('task');
            $oldPath = $attachment->path;

            $this->snapshot($attachment, 'replaced', $user);

            $newData = $this->storeCurrentFile($attachment->task, $file);
            $attachment->forceFill(array_merge($newData, [
                'user_id' => $user?->id,
            ]))->save();

            $this->deleteFileIfPresent($oldPath);

            return $attachment->fresh(['uploader', 'versions.actor']);
        });
    }

    public function softDelete(TaskAttachment $attachment, ?User $user): void
    {
        DB::transaction(function () use ($attachment, $user) {
            $oldPath = $attachment->path;

            $this->snapshot($attachment, 'deleted', $user);
            $this->deleteFileIfPresent($oldPath);
            $attachment->delete();
        });
    }

    public function restoreVersion(TaskAttachment $attachment, TaskAttachmentVersion $version, ?User $user): TaskAttachment
    {
        if ($version->task_attachment_id !== $attachment->id) {
            throw new RuntimeException('Version does not belong to the attachment.');
        }

        return DB::transaction(function () use ($attachment, $version, $user) {
            $attachment->loadMissing('task');
            $currentPath = $attachment->path;

            if ($currentPath && Storage::disk('local')->exists($currentPath)) {
                $this->snapshot($attachment, 'restored', $user);
                $this->deleteFileIfPresent($currentPath);
            }

            $newPath = $this->copyVersionToCurrent($attachment, $version);

            $attachment->forceFill([
                'user_id' => $user?->id ?? $attachment->user_id,
                'path' => $newPath,
                'original_name' => $version->original_name,
                'mime_type' => $version->mime_type,
                'size' => $version->size,
            ])->save();

            if ($attachment->trashed()) {
                $attachment->restore();
            }

            return $attachment->fresh(['uploader', 'versions.actor']);
        });
    }

    public function deleteAllFiles(TaskAttachment $attachment): void
    {
        $this->deleteFileIfPresent($attachment->path);

        $attachment->versions->each(function (TaskAttachmentVersion $version): void {
            $this->deleteFileIfPresent($version->path);
        });
    }

    private function buildAttachmentData(Task $task, UploadedFile $file, ?User $user): array
    {
        $stored = $this->storeCurrentFile($task, $file);

        return array_merge($stored, [
            'task_id' => $task->id,
            'user_id' => $user?->id,
        ]);
    }

    private function storeCurrentFile(Task $task, UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $storedName = Str::uuid().'.'.$extension;
        $directory = $this->taskDirectory($task);
        $path = Storage::disk('local')->putFileAs($directory, $file, $storedName);

        if (! $path) {
            throw new RuntimeException('Unable to store the uploaded attachment.');
        }

        return [
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => (int) ($file->getSize() ?: 0),
        ];
    }

    private function snapshot(TaskAttachment $attachment, string $action, ?User $user): TaskAttachmentVersion
    {
        if (! Storage::disk('local')->exists($attachment->path)) {
            throw new RuntimeException('Attachment file is missing from storage.');
        }

        $sourcePath = $attachment->path;
        $snapshotPath = $this->versionPath($attachment, $sourcePath);

        if (! Storage::disk('local')->copy($sourcePath, $snapshotPath)) {
            throw new RuntimeException('Unable to archive the attachment version.');
        }

        return $attachment->versions()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'path' => $snapshotPath,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
        ]);
    }

    private function copyVersionToCurrent(TaskAttachment $attachment, TaskAttachmentVersion $version): string
    {
        if (! Storage::disk('local')->exists($version->path)) {
            throw new RuntimeException('The selected version file is missing from storage.');
        }

        $extension = pathinfo($version->path, PATHINFO_EXTENSION) ?: 'bin';
        $directory = $this->taskDirectory($attachment->task);
        $newPath = $directory.'/'.Str::uuid()->toString().'.'.$extension;

        if (! Storage::disk('local')->copy($version->path, $newPath)) {
            throw new RuntimeException('Unable to restore the selected attachment version.');
        }

        return $newPath;
    }

    private function deleteFileIfPresent(?string $path): void
    {
        if (! is_string($path) || $path === '' || ! Storage::disk('local')->exists($path)) {
            return;
        }

        Storage::disk('local')->delete($path);
    }

    private function versionPath(TaskAttachment $attachment, string $sourcePath): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'bin';

        return $this->versionDirectory($attachment).'/'.Str::uuid()->toString().'.'.$extension;
    }

    private function taskDirectory(Task $task): string
    {
        return 'task-attachments/'.$task->id;
    }

    private function versionDirectory(TaskAttachment $attachment): string
    {
        return $this->taskDirectory($attachment->task).'/versions/'.$attachment->id;
    }
}
