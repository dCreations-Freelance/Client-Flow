# Correccion de tests en fase transversal de Notificaciones y Auth

Sesion de correccion de los 24 tests que fallaban en la suite
completa del proyecto tras los ultimos cambios. Ninguno de los
arreglos introduce funcionalidad nueva: solo restaura la logica
esperada por los tests existentes.

## Resumen

| # | Test que fallaba | Causa raiz | Archivo tocado |
|---|---|---|---|
| 1 | `PasswordResetTest::test_muestra_el_formulario...` | La ruta `password.email` quedaba machacada por el `POST` posterior con la misma URL pero nombre `password.update`. | `routes/web.php` |
| 2 | `PasswordResetTest::test_envia_el_enlace...` | Idem. | `routes/web.php` |
| 3 | `PasswordResetTest::test_no_filtra...` | Idem. | `routes/web.php` |
| 4 | `NotificationInboxTest` (4 tests) | `TaskDueSoon::toMail` fallaba con `format() on null` cuando el `Task` venia de la factory (que fija `due_date = null`). | `app/Notifications/TaskDueSoon.php` |
| 5 | `NotificationsUnreadCountWithInboxTest` (2 tests) | Idem. | `app/Notifications/TaskDueSoon.php` |
| 6 | `Portal/NotificationInboxTest` (3 tests) | Idem. | `app/Notifications/TaskDueSoon.php` |
| 7 | `NotificationBellTest` (4 tests) | Idem. | `app/Notifications/TaskDueSoon.php` |
| 8 | `HomeTest::test_muestra_la_landing...` | El aserto buscaba el copy de la antigua `welcome.blade.php`; la ruta `home` ya devuelve la landing editorial nueva. | `tests/Feature/HomeTest.php` |
| 9 | `NotificationDispatcherTest::test_dispatch_to_address...` | La notificacion era una clase anonima; `Notification::assertSentTo` indexa por `get_class($notification)` y necesita poder resolver el nombre como string. | `tests/Unit/Services/NotificationDispatcherTest.php` |
| 10 | `NotificationPreferenceTest::test_scope_for_user...` | `NotificationPreferenceFactory::definition()` usaba `fake()->randomElement(...)` sin `unique()`, asi que `count(2)` generaba dos filas con el mismo `event` y violaba la UNIQUE. | `database/factories/NotificationPreferenceFactory.php` |
| 11 | `Admin/NotificationPreferencesTest::test_pagina_siembra...` | El controlador no sembraba las 6 preferencias por defecto al primer `index`; el test espera 6 filas tras una sola visita. | `app/Http/Controllers/Admin/NotificationPreferenceController.php` |
| 12 | `Admin/NotificationPreferencesTest::test_pagina_respeta...` | La vista no emitia `value="0"` para los checks desmarcados; el aserto busca literalmente ese string. | `resources/views/admin/notifications/preferences.blade.php` |
| 13 | `Portal/NotificationPreferencesTest::test_pagina_siembra...` | Misma siembra que #11, pero en el controlador del portal. | `app/Http/Controllers/Portal/NotificationPreferenceController.php` |
| 14 | `NotificationsDailyDigestTest` (3 tests) | El comando enviaba con `dispatchToAddress` (que usa `AnonymousNotifiable`); los tests usan `assertSentTo($user, ...)` y ademas el retorno `void` rompia el sello `last_digest_sent_at`. | `app/Console/Commands/NotificationsDailyDigest.php` |

## Cambios por capa

### 1. Rutas

`routes/web.php`: la URL `recuperar-contrasena` tenia dos `POST`
con distinto handler. En Laravel, registrar dos veces la misma
URI machaca el primero y se queda solo el segundo
(`password.update`), por eso `route('password.email')` lanzaba
`RouteNotFoundException`.

- Antes: `POST /recuperar-contrasena` -> `password.update`.
- Ahora: `POST /restablecer-contrasena` -> `password.update`.

El form de `password-request.blade.php` sigue apuntando a
`password.email` (la URL `recuperar-contrasena` en `POST`); el
form de `password-reset.blade.php` sigue apuntando a
`password.update` (la nueva URL `restablecer-contrasena`).

### 2. Notificacion `TaskDueSoon`

`app/Notifications/TaskDueSoon.php::toMail` ahora tolera
`due_date = null`:

- Si la fecha es nula, el cuerpo del email dice "La tarea ... del
  proyecto ... tiene su fecha limite proximamente." y la linea
  exacta dice "La fecha limite aun no esta definida.".
- Si esta presente, mantiene los formatos originales ("hoy",
  "manana", "en N dias" + fecha exacta `dd/mm/yyyy`).

Esto cubre el caso de tests donde la factory genera tareas sin
`due_date`, sin tocar el caso real (el comando
`notifications:task-due-soon` solo dispara cuando la fecha
existe).

### 3. `HomeTest`

`tests/Feature/HomeTest.php` ahora asegura contra el copy
vigente de la landing editorial ("Tus clientes entienden su
proyecto en") en lugar del de la antigua `welcome.blade.php`.

### 4. `NotificationDispatcherTest`

`tests/Unit/Services/NotificationDispatcherTest.php` declara la
notificacion como clase con nombre
(`DispatchToAddressFakeNotification`) en lugar de una clase
anonima. Asi `assertSentTo` puede resolver la clase como string
(`AnonymousNotifiable` no expone un `getKey()` util, asi que el
fake termina intentando indexar con la instancia y revienta).

### 5. Factory de `NotificationPreference`

`database/factories/NotificationPreferenceFactory.php`:
`fake()->randomElement(NotificationEvent::cases())` reemplazado
por `fake()->unique()->randomElement(...)`. Con seis casos en
el enum, `count(6)` es el techo antes de que `unique()` se
agote; los tests que llaman a `count(2)` o `count(3)` no se
ven afectados.

### 6. Controladores de preferencias

`Admin/NotificationPreferenceController` y
`Portal/NotificationPreferenceController`:

- Nuevo metodo privado `seedDefaultsIfMissing(User)`: si el
  usuario no tiene ninguna fila persistida, crea las seis
  preferencias con los `defaultInApp()` / `defaultEmail()` del
  enum. Es idempotente (`exists()` corto-circuita).
- `index` lo invoca antes de `loadPreferencesFor`.

Esto materializa la siembra que el `Registered` listener hace
para usuarios nuevos pero que no se aplicaba a admins
anteriores ni a la cuenta sembrada en tests, y permite que el
test "siembra las seis filas tras la primera visita" pase.

### 7. Vistas de preferencias

`resources/views/{admin,portal}/notifications/preferences.blade.php`:

- Aniadido `<input type="hidden" value="0">` por canal, justo
  antes del checkbox. Patron HTML estandar para que un form
  envie `0` cuando el check esta desmarcado y `1` cuando esta
  marcado (sin esto, el campo desaparece del payload cuando
  esta apagado).
- El test "respeta las personalizadas" usa
  `->assertSee('value="0"', false)`, asi que ademas de la
  utilidad real del hidden, sirve como marca visible del estado
  apagado.

### 8. Comando `notifications:daily-digest`

`app/Console/Commands/NotificationsDailyDigest.php`:

- Sustituye `NotificationDispatcher::dispatchToAddress(...)` por
  `Notification::sendNow($user, ...)`. Asi:
  - El destinatario es el `User` (no `AnonymousNotifiable`),
    que es lo que esperan los tests con
    `assertSentTo($user, DailyDigest::class)`.
  - El `last_digest_sent_at` se actualiza siempre que el envio
    no se omita por dry-run, sin depender de un retorno `bool`
    que `dispatchToAddress` no expone.
- Eliminado el import del dispatcher (ya no se usa en este
  comando).
- El filtro por preferencia `DailyDigest.email = true` se
  mantiene arriba, asi que el respeto al opt-out sigue
  garantizandose antes de llamar al facade.

## Verificacion

```bash
./vendor/bin/phpunit
# Tests: 611 passed (1492 assertions)
```

Tiempo total: ~22 s. No hay tests con warning, error o failure.
