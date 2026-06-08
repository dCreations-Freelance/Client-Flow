<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('goal')->nullable();
            $table->string('status')->default('planning')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('current_phase')->nullable();
            $table->string('next_milestone')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('estimated_ends_at')->nullable();
            $table->string('cover_path')->nullable();
            $table->boolean('is_visible_to_client')->default(true)->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
