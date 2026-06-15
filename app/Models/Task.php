<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    private const STATUS_BADGE_CLASSES = [
        'open' => 'bg-info text-dark',
        'in_progress' => 'bg-warning text-dark',
        'completed' => 'bg-success',
        'on_hold' => 'bg-secondary',
        'to_review' => 'bg-info text-dark',
    ];

    private const PRIORITY_BADGE_CLASSES = [
        'low' => 'bg-success',
        'medium' => 'bg-warning text-dark',
        'high' => 'bg-danger',
    ];

    protected $fillable = [
        'client_id',
        'assigned_user_id',
        'title',
        'description',
        'status',
        'priority',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class)->latest()->orderByDesc('id');
    }

    public function progressEntries()
    {
        return $this->hasMany(TaskProgressEntry::class)->orderByDesc('date')->orderByDesc('id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function activityEntries()
    {
        return $this->hasMany(TaskActivityEntry::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function statusLabel(): string
    {
        return $this->formatLabel($this->status);
    }

    public function priorityLabel(): string
    {
        return $this->formatLabel($this->priority);
    }

    public static function statusOptions(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'to_review' => 'To Review',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
        ];
    }

    public static function statusValues(): array
    {
        return array_keys(self::statusOptions());
    }

    public function statusBadgeClass(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'bg-light text-dark border';
    }

    public function priorityBadgeClass(): string
    {
        return self::PRIORITY_BADGE_CLASSES[$this->priority] ?? 'bg-light text-dark border';
    }

    protected static function booted(): void
    {
        static::deleting(function (Task $task) {
            $task->loadMissing('attachments');

            foreach ($task->attachments as $attachment) {
                Storage::disk('local')->delete($attachment->path);
            }
        });
    }

    private function formatLabel(?string $value): string
    {
        return $value ? Str::headline(str_replace('_', ' ', $value)) : 'N/A';
    }
}
