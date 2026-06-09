<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visual_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->index();
            $table->string('media_path');
            $table->string('media_file_name');
            $table->string('media_mime_type');
            $table->unsignedBigInteger('media_size');
            $table->string('thumbnail_path')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->string('visibility')->default('public')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visual_entries');
    }
};
