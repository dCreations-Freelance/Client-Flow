<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivote entre `users` y `projects`. Un proyecto solo puede tener
     * a cada usuario una vez. Las claves foraneas hacen cascade al
     * eliminar para mantener la coherencia cuando un proyecto o un
     * usuario desaparecen.
     */
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
