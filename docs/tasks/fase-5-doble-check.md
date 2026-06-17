# Fase 5 — Doble check de leído (visto) en el chat

Documento técnico del sub-módulo que cierra la fase 5 del MVP de
ClientFlow: implementación del doble check de lectura en los
mensajes del chat por proyecto.

## Alcance

Según `TODOs.md`, faltaban por completar en la fase 5:

- Crear migración `message_reads` (pivot `message_id`, `user_id`,
  `read_at`).
- Implementar doble check de leído (visto) en mensajes.
- Mostrar indicador "Visto" en burbujas propias al ser leído.
- Marcar mensajes como leídos al abrir el chat (polling).

## Cambios por capa

### 1. Migraciones

| Archivo | Propósito |
|---|---|
| `database/migrations/2026_06_17_054449_create_message_reads_table.php` | Crea `message_reads` con FK a `project_messages` (cascade) y `users` (cascade), `read_at`, unique `(message_id, user_id)` e índice `(user_id, read_at)`. |

Decisiones:

- Se mantiene `project_chat_reads` intacta. Sigue siendo la fuente
  de verdad eficiente para contar no leídos por proyecto.
- `message_reads` responde a la pregunta "¿quién ha visto este
  mensaje concreto?", necesaria para el doble check.
- `read_at` se guarda explícitamente (además de `created_at`) para
  poder consultar cuándo se leyó sin depender de los timestamps del
  modelo.

### 2. Modelos

#### `MessageRead`

`app/Models/MessageRead.php`:

- `$fillable`: `message_id`, `user_id`, `read_at`.
- Casts: `read_at` → datetime.
- Relaciones: `message()` BelongsTo, `user()` BelongsTo.
- Método estático `markMessagesAsRead(Project, User, int): void`:
  - Inserta un registro por cada mensaje del proyecto con `id <=
    upToMessageId` que el usuario aún no haya leído.
  - Es idempotente: consulta primero los ids ya leídos para evitar
    duplicados sin depender de excepciones de BD.
  - Usa inserción masiva directa (`DB::table`) para no penalizar el
    polling.

#### `ProjectMessage`

`app/Models/ProjectMessage.php`:

- Nueva relación `reads(): HasMany<MessageRead>`.
- Nuevos helpers:
  - `isReadBy(User): bool`.
  - `readByAnyoneElse(User): bool` — condición del doble check.
    Devuelve `true` si alguien distinto al emisor ha leído el
    mensaje.

### 3. Componente Livewire

`App\Livewire\Shared\ChatWindow`:

- Importa y usa `MessageRead`.
- `markAsRead()` ahora actualiza ambos mecanismos:
  1. `ProjectChatRead::markAsRead` para el contador de no leídos.
  2. `MessageRead::markMessagesAsRead` para el tracking individual.
- Nueva propiedad computada `getReadMessageIdsProperty(): array`:
  - Devuelve un mapa `[message_id => true]` con los mensajes
    cargados que han sido leídos por alguien distinto al usuario
    actual.
  - Se calcula con una única query `whereIn` para evitar N+1.
- El método `render()` pasa `readMessageIds` a la vista.

### 4. Vistas Blade

`resources/views/livewire/shared/chat-window.blade.php`:

- Pasa la propiedad `:isReadByOther` al partial `chat-message`.

`resources/views/components/partials/chat-message.blade.php`:

- En mensajes propios, muestra junto a la hora:
  - `✓` si nadie más lo ha leído (tooltip "Enviado").
  - `✓✓` en azul si al menos otro usuario lo ha leído (tooltip
    "Visto").
- No muestra el indicador en mensajes ajenos ni en mensajes de
  sistema.

### 5. Factory

`database/factories/MessageReadFactory.php`:

- Define estado por defecto para `message_id`, `user_id` y
  `read_at`, permitiendo usarla en tests sin especificar todos los
  campos.

### 6. Tests

Total añadido: **16 tests** distribuidos en:

| Archivo | Tests nuevos |
|---|---|
| `tests/Unit/Models/MessageReadTest.php` | 6 |
| `tests/Feature/Livewire/Shared/ChatWindowTest.php` | 3 |
| `tests/Feature/Admin/ChatManagementTest.php` | 1 |
| `tests/Feature/Portal/ChatViewTest.php` | 1 |

Cubren:

- Creación masiva de registros de lectura.
- Idempotencia del marcado.
- Respeto del límite superior (`upToMessageId`).
- `readByAnyoneElse` distingue entre lectura propia y de terceros.
- El componente Livewire marca como leídos los mensajes al montar.
- Al enviar un mensaje no se crea un `message_read` del emisor.
- La propiedad computada refleja el cambio cuando otro usuario lee.
- Desde admin y portal, un mensaje propio muestra doble check tras
  ser leído por otro usuario.

## Decisiones técnicas relevantes

1. **Dos tablas de lectura en lugar de una**:
   `project_chat_reads` sigue siendo la opción eficiente para el
   contador de no leídos. `message_reads` añade granularidad por
   mensaje sin romper el rendimiento del contador.

2. **Doble check por al menos un destinatario**:
   El indicador "Visto" se activa cuando alguien distinto al emisor
   ha leído el mensaje. Es el comportamiento más común en apps de
   mensajería y suficiente para el MVP.

3. **El emisor no genera `message_read` de sí mismo**:
   Al enviar un mensaje solo se marca como leído en
   `project_chat_reads` (para que no le cuente como no leído). El
   doble check mide exclusivamente lecturas de otros usuarios.

4. **Inserción masiva con prevención de duplicados**:
   `markMessagesAsRead` consulta los ids ya leídos y usa
   `DB::table('message_reads')->insert($rows)`. Así el polling cada
   5 s no genera intentos de inserción duplicados ni múltiples
   queries.

5. **Mapa de ids leídos en la vista**:
   En lugar de evaluar `readByAnyoneElse` por cada mensaje en Blade
   (lo que causaría N+1 queries), el componente calcula un array de
   ids y la vista hace una lookup en tiempo constante.

## Verificación final

```bash
cd app
php artisan test
```

Resultado:

- **252 tests pasan, 602 aserciones** (+16 tests y +33 aserciones
  respecto a la fase 5 base).
- Migraciones validadas vía `RefreshDatabase` (SQLite en memoria).

## Pendiente (fuera de scope)

- Edición/borrado de mensajes.
- Reacciones, hilos o respuestas específicas.
- WebSockets / real-time push.
- Vista unificada de notificaciones in-app (campana/dropdown).
