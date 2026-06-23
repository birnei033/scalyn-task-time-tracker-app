<?php

namespace App\Models;

use Database\Factories\TaskAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskAttachment extends Model
{
    /** @use HasFactory<TaskAttachmentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versions()
    {
        return $this->hasMany(TaskAttachmentVersion::class)->orderByDesc('id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function displaySize(): string
    {
        $bytes = max(0, (int) $this->size);

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $kilobytes = $bytes / 1024;

        if ($kilobytes < 1024) {
            return number_format($kilobytes, 1).' KB';
        }

        return number_format($kilobytes / 1024, 1).' MB';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withTrashed()
            ->where($field ?: $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
