<?php

namespace Database\Seeders;

use App\Enums\CalendarEventType;
use App\Enums\OrganizationUserRole;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\CalendarEvent;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder base para tener un entorno navegable al instalar.
 *
 * Crea un admin (`admin@clientflow.test` / `password`), un cliente
 * (`cliente@clientflow.test` / `password`), una organizacion donde
 * el cliente es miembro, y un proyecto demo con tareas, eventos
 * de calendario y deadlines virtuales para que el calendario
 * tenga contenido visible nada mas arrancar.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@clientflow.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ]
        );

        $client = User::firstOrCreate(
            ['email' => 'cliente@clientflow.test'],
            [
                'name' => 'Cliente Demo',
                'password' => Hash::make('password'),
                'role' => UserRole::Client,
            ]
        );

        // Comprobamos primero si la organizacion existe para evitar el
        // camino de `firstOrCreate` con la columna `slug` (que se genera
        // en un evento del modelo y por seguridad la creamos con el slug
        // ya calculado).
        $organization = Organization::where('name', 'Cliente Demo S.L.')->first();

        if ($organization === null) {
            $organization = Organization::create([
                'name' => 'Cliente Demo S.L.',
                'slug' => Organization::generateUniqueSlug('Cliente Demo S.L.'),
                'description' => 'Organizacion de ejemplo para que el cliente pueda explorar el portal.',
                'owner_id' => $admin->id,
            ]);
        }

        if (! $organization->members()->where('users.id', $admin->id)->exists()) {
            $organization->members()->attach($admin->id, [
                'role' => OrganizationUserRole::Owner->value,
            ]);
        }

        if (! $organization->members()->where('users.id', $client->id)->exists()) {
            $organization->members()->attach($client->id, [
                'role' => OrganizationUserRole::Member->value,
            ]);
        }

        // Proyecto demo: solo lo creamos si la organizacion esta
        // vacia de proyectos, para no duplicarlo en sucesivos
        // `migrate:fresh --seed`.
        $project = $organization->projects()->first();

        if ($project === null) {
            $project = Project::create([
                'organization_id' => $organization->id,
                'name' => 'Proyecto Demo',
                'slug' => Project::generateUniqueSlug('Proyecto Demo'),
                'description' => 'Proyecto de ejemplo con contenido para que el calendario tenga eventos y tareas visibles.',
                'status' => ProjectStatus::InProgress,
                'is_visible_to_client' => true,
            ]);

            $project->members()->attach($admin->id);
        }

        // Crear las columnas default del proyecto solo si no tiene
        // ya (idempotencia). Se hace aqui para que el seeder no
        // delegue en el servicio y se pueda reproducir el slug de
        // cada columna de forma explicita.
        if ($project->columns()->doesntExist()) {
            $defaults = [
                ['name' => 'Por hacer', 'slug' => 'por-hacer', 'color' => '#94A3B8', 'position' => 0],
                ['name' => 'En curso', 'slug' => 'en-curso', 'color' => '#2563EB', 'position' => 1],
                ['name' => 'En revision', 'slug' => 'en-revision', 'color' => '#D97706', 'position' => 2],
                ['name' => 'Hecho', 'slug' => 'hecho', 'color' => '#16A34A', 'position' => 3],
            ];
            foreach ($defaults as $col) {
                \App\Models\BoardColumn::create([
                    'project_id' => $project->id,
                    'name' => $col['name'],
                    'slug' => $col['slug'],
                    'color' => $col['color'],
                    'position' => $col['position'],
                    'is_default' => true,
                ]);
            }
        }

        // Eventos de calendario: dos meetings proximos y un
        // milestone all-day. Solo se crean si el proyecto no
        // tiene eventos, para que `migrate:fresh --seed` sea
        // idempotente.
        if ($project->calendarEvents()->doesntExist()) {
            $nextWeek = Carbon::now()->addDays(7);

            $meeting1 = CalendarEvent::create([
                'project_id' => $project->id,
                'title' => 'Reunion de kickoff',
                'description' => 'Sesion inicial para presentar el alcance y el equipo.',
                'type' => CalendarEventType::Meeting,
                'starts_at' => $nextWeek->copy()->setTime(10, 0),
                'ends_at' => $nextWeek->copy()->setTime(11, 0),
                'is_all_day' => false,
                'created_by' => $admin->id,
            ]);
            $meeting1->attendees()->attach([$admin->id, $client->id]);

            $meeting2 = CalendarEvent::create([
                'project_id' => $project->id,
                'title' => 'Demo de avance',
                'description' => 'Mostraremos los primeros entregables al cliente.',
                'type' => CalendarEventType::Meeting,
                'starts_at' => $nextWeek->copy()->addDays(7)->setTime(16, 0),
                'ends_at' => $nextWeek->copy()->addDays(7)->setTime(17, 30),
                'is_all_day' => false,
                'created_by' => $admin->id,
            ]);
            $meeting2->attendees()->attach([$admin->id, $client->id]);

            CalendarEvent::create([
                'project_id' => $project->id,
                'title' => 'Entrega de fase 1',
                'description' => 'Hito de cierre de la primera fase del proyecto.',
                'type' => CalendarEventType::Milestone,
                'starts_at' => $nextWeek->copy()->addDays(21)->startOfDay(),
                'ends_at' => $nextWeek->copy()->addDays(21)->endOfDay(),
                'is_all_day' => true,
                'created_by' => $admin->id,
            ]);
        }

        // Tareas con `due_date` para que el calendario muestre
        // deadlines virtuales. Aprovechamos las columnas por
        // defecto generadas en `ensureDefaultBoardColumns`.
        if ($project->tasks()->doesntExist()) {
            $firstColumn = $project->columns()->ordered()->first();
            $inProgressColumn = $project->columns()->where('slug', 'in_progress')->first() ?? $firstColumn;

            if ($firstColumn !== null) {
                $task1 = Task::create([
                    'project_id' => $project->id,
                    'column_id' => $firstColumn->id,
                    'title' => 'Diseno de la landing',
                    'description' => 'Maquetar la pagina de inicio con la paleta warm.',
                    'priority' => 'high',
                    'type' => 'feature',
                    'due_date' => Carbon::now()->addDays(3),
                    'created_by' => $admin->id,
                ]);
                $task1->update(['position' => 1]);

                $task2 = Task::create([
                    'project_id' => $project->id,
                    'column_id' => $inProgressColumn?->id ?? $firstColumn->id,
                    'title' => 'Integracion con API de pagos',
                    'description' => 'Conectar el checkout con el proveedor.',
                    'priority' => 'critical',
                    'type' => 'feature',
                    'due_date' => Carbon::now()->addDays(10),
                    'created_by' => $admin->id,
                ]);
                $task2->update(['position' => 1]);
            }
        }
    }
}
