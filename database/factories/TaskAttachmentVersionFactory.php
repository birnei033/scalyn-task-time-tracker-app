<?php

namespace Database\Factories;

use App\Models\TaskAttachment;
use App\Models\TaskAttachmentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TaskAttachmentVersion>
 */
class TaskAttachmentVersionFactory extends Factory
{
    protected $model = TaskAttachmentVersion::class;

    public function definition(): array
    {
        $extension = $this->faker->randomElement(['pdf', 'txt', 'png', 'docx']);

        return [
            'task_attachment_id' => TaskAttachment::factory(),
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['created', 'replaced', 'deleted', 'restored']),
            'path' => 'task-attachments/'.Str::uuid()->toString().'/versions/'.Str::uuid()->toString().'.'.$extension,
            'original_name' => $this->faker->word().'.'.$extension,
            'mime_type' => match ($extension) {
                'png' => 'image/png',
                'txt' => 'text/plain',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'application/pdf',
            },
            'size' => $this->faker->numberBetween(1024, 512000),
        ];
    }
}
