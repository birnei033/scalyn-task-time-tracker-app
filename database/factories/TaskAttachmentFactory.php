<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    protected $model = TaskAttachment::class;

    public function definition(): array
    {
        $extension = $this->faker->randomElement(['pdf', 'txt', 'png', 'docx']);

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'path' => 'task-attachments/'.Str::uuid()->toString().'/'.Str::uuid()->toString().'.'.$extension,
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
