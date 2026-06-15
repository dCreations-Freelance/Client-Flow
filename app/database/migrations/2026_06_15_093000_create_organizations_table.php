<?php

use App\Enums\OrganizationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `organizations` segun `docs/DATA_MODEL.md`. El `slug`
     * se usa en URLs amigables y debe ser unico. `owner_id` referencia
     * al admin que creo la organizacion. `status` es un enum con valores
     * `active` e `inactive` para permitir organizaciones desactivadas
     * sin necesidad de borrado fisico.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('status')
                ->default(OrganizationStatus::Active->value)
                ->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
