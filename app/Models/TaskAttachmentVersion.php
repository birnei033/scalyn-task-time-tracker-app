<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAttachmentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_attachment_id',
        'user_id',
        'action',
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

    public function attachment()
    {
        return $this->belongsTo(TaskAttachment::class, 'task_attachment_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
