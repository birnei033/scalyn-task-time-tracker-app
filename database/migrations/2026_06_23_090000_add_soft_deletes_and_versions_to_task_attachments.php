<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_attachments', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::create('task_attachment_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_attachment_id')->constrained('task_attachments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 191)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['task_attachment_id', 'id']);
            $table->index(['task_attachment_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachment_versions');

        Schema::table('task_attachments', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
