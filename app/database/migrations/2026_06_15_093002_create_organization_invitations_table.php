<?php

use App\Enums\OrganizationUserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `organization_invitations`. Almacena invitaciones
     * pendientes para que un email se una a una organizacion. El token
     * almacenado es el hash del token real (el crudo solo se entrega en
     * el email), siguiendo la guia de `docs/IMPLEMENTATION.md` sobre
     * tokens hasheados.
     */
    public function up(): void
    {
        Schema::create('organization_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->string('email');
            $table->string('token')->unique();
            $table->string('role')
                ->default(OrganizationUserRole::Member->value);
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
    }
};
