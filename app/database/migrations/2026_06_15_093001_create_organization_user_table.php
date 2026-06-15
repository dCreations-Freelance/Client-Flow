<?php

use App\Enums\OrganizationUserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote entre `users` y `organizations`. La columna `role`
     * distingue al owner del miembro regular. Se anade `unique`
     * compuesto para evitar que un mismo usuario este dos veces en la
     * misma organizacion. Las claves foraneas hacen cascade al eliminar
     * para mantener la coherencia con la UI.
     */
    public function up(): void
    {
        Schema::create('organization_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('role')
                ->default(OrganizationUserRole::Member->value);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};
