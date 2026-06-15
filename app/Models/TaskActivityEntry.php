<?php

namespace App\Models;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaskActivityEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'action',
        'field',
        'old_value',
        'new_value',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fieldLabel(): string
    {
        return match ($this->field) {
            'client_id' => 'Client',
            'assigned_user_id' => 'Assigned user',
            'title' => 'Title',
            'description' => 'Description',
            'status' => 'Status',
            'priority' => 'Priority',
            default => $this->field ? Str::headline(str_replace('_', ' ', $this->field)) : 'Task',
        };
    }

    public function summary(): string
    {
        if ($this->action === 'created') {
            return 'Task created';
        }

        if ($this->action === 'updated' && $this->field) {
            return sprintf(
                '%s changed from %s to %s',
                $this->fieldLabel(),
                $this->formattedOldValue(),
                $this->formattedNewValue(),
            );
        }

        return 'Task activity recorded';
    }

    public function formattedOldValue(): string
    {
        return $this->formatValue($this->field, $this->old_value);
    }

    public function formattedNewValue(): string
    {
        return $this->formatValue($this->field, $this->new_value);
    }

    private function formatValue(?string $field, ?string $value): string
    {
        if ($value === null || $value === '') {
            return 'Not set';
        }

        return match ($field) {
            'status', 'priority' => Str::headline(str_replace('_', ' ', $value)),
            'description' => RichText::excerpt($value),
            default => $value,
        };
    }
}
