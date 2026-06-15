# Fase 1 — Foundation (Auth + Roles + Organizations)

Documento tecnico de lo implementado en la fase 1 del MVP de ClientFlow.
Cubre autenticacion, gestion de roles, ciclo de invitaciones a
organizaciones y dashboards basicos de admin y portal.

## Alcance

Segun `TODOs.md`, la fase 1 incluye:

- Enum `UserRole` con `admin` y `client`.
- Campo `role` en la tabla `users` (default `client`, indexado).
- Middleware `EnsureUserIsAdmin` y `EnsureUserIsClient`.
- Aliases en `bootstrap/app.php`.
- Login con redireccion por rol.
- Registro (solo rol `client`).
- Recuperacion de password.
- Layouts `auth`, `admin` y `portal`.
- Vistas de login, register, password.request, password.reset.
- Rutas `/admin/*` protegidas con `admin`, `/portal/*` con `client`.
- Migraciones `organizations`, `organization_user` y `organization_invitations`.
- Modelos `Organization` y `OrganizationInvitation` con relaciones.
- CRUD de organizaciones (admin).
- Invitacion de miembros por email.
- Vista de aceptacion de invitacion.
- Vista de miembros de la organizacion (admin).
- Dashboards de admin y portal.
- Welcome page con CTAs.

## Cambios por capa

### 1. Migraciones

| Archivo | Proposito |
|---|---|
| `database/migrations/2026_06_15_092359_add_role_to_users_table.php` | Anade `role` a `users` (enum admin/client, default client, index). |
| `database/migrations/2026_06_15_093000_create_organizations_table.php` | Crea `organizations` con `owner_id` (cascade), `slug` unique, `status` enum. |
| `database/migrations/2026_06_15_093001_create_organization_user_table.php` | Pivote con `role` (owner/member), unique `(organization_id, user_id)`. |
| `database/migrations/2026_06_15_093002_create_organization_invitations_table.php` | Almacena invitaciones con `token` (hash), `expires_at`, `accepted_at`. |

Todas las claves foraneas usan `cascadeOnDelete` para mantener la
consistencia referencial cuando se elimina un usuario u organizacion.
Los indices se aplican a las columnas por las que se filtra
frecuentemente: `users.role`, `organizations.status` y
`(organization_id, email)` en invitaciones.

### 2. Enums

| Archivo | Valores |
|---|---|
| `app/Enums/UserRole.php` | `Admin`, `Client` con `label()`, `isAdmin()`, `isClient()`. |
| `app/Enums/OrganizationStatus.php` | `Active`, `Inactive` con `label()` y `color()`. |
| `app/Enums/OrganizationUserRole.php` | `Owner`, `Member` con `label()` e `isOwner()`. |

Todos los enums son `string backed` para que el valor en BD sea legible
y portable. Los casts en los modelos se encargan de la conversion
automatica, lo que permite type-hints como `OrganizationStatus` en
policies y vistas.

### 3. Modelos

- `User` (existente, ampliado):
  - `role` agregado a `$fillable` y `$casts` (`UserRole::class`).
  - Helpers `isAdmin()` e `isClient()` para evitar string comparisons.
  - Relaciones declaradas: `organizations()`, `ownedOrganizations()`,
    `projects()` y `sentInvitations()`. Las dos ultimas son firmas
    adelantadas para mantener la forma del modelo alineada con
    `docs/DATA_MODEL.md` y compilar sin errores en fases futuras.

- `Organization`:
  - `$fillable` con `name`, `slug`, `description`, `logo_path`,
    `owner_id`, `status`.
  - Evento `creating` que genera el slug unico a partir del nombre.
  - Relaciones: `owner()` (BelongsTo), `members()` (BelongsToMany
    con `withPivot('role')`), `owners()` (subset con rol owner),
    `projects()` y `invitations()` (HasMany, firma adelantada para
    fase 2).
  - Scopes `active()` y `forUser(User)` para filtros de uso comun.
  - Helpers: `generateUniqueSlug(string)` es un metodo estatico que
    usa `Str::slug` y anade sufijo numerico incremental en caso de
    colision.

- `OrganizationInvitation`:
  - Implementa `Notifiable` para poder enviar el email desde el
    modelo (`$invitation->notify(...)`).
  - Casts: `expires_at` y `accepted_at` a `datetime`, `role` a enum.
  - Relaciones `organization()` y `creator()`.
  - Helpers: `isAccepted()`, `isExpired()`, `isUsable()`,
    `markAccepted()`. Scope `usable()` para queries limpias.

### 4. Policies

- `OrganizationPolicy`:
  - `viewAny` y `create`: solo admin.
  - `view`: admin o miembro de la organizacion.
  - `update`, `delete`, `invite`, `manageMembers`: solo admin.
- `OrganizationInvitationPolicy`:
  - `accept`: el email del usuario autenticado debe coincidir con el
    de la invitacion y la invitacion debe estar vigente.
  - `revoke`: solo admin.

Las policies se autorresuelven por convencion de nombre (Laravel 11+
discovery). El base `Controller` ahora incluye los traits
`AuthorizesRequests` y `ValidatesRequests` para que los helpers
`$this->authorize()` y `$this->validate()` esten disponibles.

### 5. Servicios

- `OrganizationInvitationService`:
  - `create(Organization, email, role, inviter)` genera un token
    crudo con `Str::random(64)`, guarda el hash en BD y devuelve la
    invitacion + el token en claro para construir el enlace del
    email.
  - `findByRawToken(string)` busca la invitacion cuyo hash coincide
    con el token recibido, restringiendo a invitaciones vigentes
    (no expiradas, no aceptadas).
  - `accept(Invitation, User)` une al usuario a la organizacion con
    el rol correspondiente y marca la invitacion como aceptada. Es
    idempotente: si el usuario ya es miembro no duplica la fila del
    pivot, y aceptar dos veces la misma invitacion no produce
    duplicados.

### 6. Form Requests

- `Auth/LoginRequest.php`: email + password + remember.
- `Auth/RegisterRequest.php`: nombre, email, password + confirmacion.
  El metodo `createUser()` fuerza el rol `client` para impedir
  registro publico de administradores.
- `Auth/PasswordResetLinkRequest.php`: email (no se valida que exista
  para no filtrar cuentas registradas).
- `Auth/NewPasswordRequest.php`: token, email, password + confirmacion.
- `Auth/AcceptInvitationRequest.php`: nombre + password + confirmacion
  para el caso de usuario nuevo.
- `Admin/StoreOrganizationRequest.php`: name (min 2) + description.
- `Admin/UpdateOrganizationRequest.php`: name + description + status
  (regla `Rule::in` contra el enum).
- `Admin/InviteMemberRequest.php`: email + role. Normaliza el email
  a minusculas en `prepareForValidation()`.

Todos los Form Requests implementan `authorize()` con check de rol
para no depender exclusivamente del middleware (defensa en
profundidad).

### 7. Controladores

- `Auth/AuthenticatedSessionController`:
  - `create` muestra el formulario de login.
  - `store` autentica con `Auth::attempt`, regenera la sesion y
    redirige a `/admin/dashboard` o `/portal/dashboard` segun rol.
  - `destroy` cierra sesion, invalida sesion y token CSRF.
- `Auth/RegisteredUserController`: alta de cliente, login automatico.
- `Auth/PasswordResetLinkController`: enlace generico de recuperacion
  (no filtra existencia del email).
- `Auth/NewPasswordController`: aplica la nueva contrasena validando
  el token del broker.
- `Auth/InvitationAcceptanceController`:
  - `show` decide entre mostrar formulario (visitante) o aceptar
    directamente (usuario autenticado con email coincidente).
  - `store` crea la cuenta si no existe, la une a la organizacion y
    la loguea.
- `Admin/OrganizationController`: CRUD con busqueda y filtro status.
  Tras crear, el admin actual queda como `owner`.
- `Admin/OrganizationMemberController`: invitacion (`store`),
  eliminacion de miembros (`destroy`) con salvaguarda para no
  eliminar al unico owner.
- `Admin/DashboardController`: contadores + organizaciones recientes.
- `Portal/DashboardController`: organizaciones del cliente con su rol
  en el pivot.

### 8. Middleware

- `EnsureUserIsAdmin`: si no es admin, redirige a `/portal/dashboard`.
  Si no hay usuario, lo deja pasar al middleware `auth` que
  redirige a login.
- `EnsureUserIsClient`: comportamiento simetrico. Ambos usan
  redireccion en lugar de 403 para guiar al usuario a su zona
  natural.

### 9. Rutas (`routes/web.php`)

```
/                                         home
/login, /register                         (guest)
/password/reset                           (guest)
/password/email, /password/reset/{token}  (guest)
/invitation/{token}                       publico
/logout                                   (auth)

/admin/dashboard                          (auth, admin)
/admin/organizations                      (auth, admin, resource)
/admin/organizations/{org}/members        (auth, admin)
/admin/organizations/{org}/members/{user} (auth, admin, DELETE)

/portal/dashboard                         (auth, client)
```

Total: 26 rutas custom. La redireccion post-login es por rol: admin
va a `/admin/dashboard`, cliente a `/portal/dashboard`.

### 10. Comandos

- `clientflow:create-admin`: crea un admin nuevo o convierte uno
  existente (con confirmacion). Es la unica via para crear el primer
  admin, ya que el registro publico solo produce `client`. En
  produccion se invocaria una sola vez o tras un reset.

### 11. Vistas Blade

- `welcome.blade.php`: landing publica con CTAs.
- `components/layouts/auth.blade.php`: card centrada, fondo warm.
- `components/layouts/admin.blade.php`: sidebar 240px + header.
- `components/layouts/portal.blade.php`: sidebar 220px.
- `partials/admin-sidebar.blade.php`: items se muestran solo si la
  ruta existe (`Route::has`), evitando links rotos entre fases.
- `partials/portal-sidebar.blade.php`: misma idea, mas minimalista.
- `partials/user-menu.blade.php`: avatar con iniciales y boton
  logout.
- `components/ui/input.blade.php`, `button.blade.php`,
  `card.blade.php`, `alert.blade.php`: componentes anonimos
  reutilizables, alineados con `docs/DESIGN.md`.
- `auth/login.blade.php`, `auth/register.blade.php`,
  `auth/password-request.blade.php`, `auth/password-reset.blade.php`,
  `auth/invitation.blade.php`.
- `admin/dashboard.blade.php`: stats + organizaciones recientes.
- `admin/organizations/index.blade.php` (listado con buscador y
  filtro), `create.blade.php`, `show.blade.php` (detalle con
  miembros e invitaciones pendientes), `edit.blade.php`,
  `members.blade.php` (gestion de miembros).
- `portal/dashboard.blade.php`: saludo + stats + tarjetas de
  organizaciones del cliente.

## Tests

Total: **72 tests, 186 aserciones, todos en verde**. Distribucion:

```
tests/Feature/Auth/AuthenticationTest.php           7 tests
tests/Feature/Auth/RegistrationTest.php             6 tests
tests/Feature/Auth/PasswordResetTest.php            6 tests
tests/Feature/Auth/InvitationAcceptanceTest.php     7 tests
tests/Feature/RoleAccessTest.php                    7 tests
tests/Feature/Admin/OrganizationManagementTest.php  9 tests
tests/Feature/Admin/MemberManagementTest.php        6 tests
tests/Feature/Admin/DashboardTest.php               2 tests
tests/Feature/Portal/DashboardTest.php              3 tests
tests/Feature/HomeTest.php                          1 test
tests/Feature/Console/CreateAdminUserCommandTest.php 2 tests
tests/Unit/Models/OrganizationTest.php              8 tests
tests/Unit/Services/OrganizationInvitationServiceTest.php  6 tests
```

Cubren:
- Caminos felices y bordes de cada flujo de auth.
- Redireccion por rol y expulsion entre zonas.
- Hashing de tokens y aceptacion idempotente.
- Eliminacion del unico owner (prohibida) y de owners adicionales
  (permitida).
- Filtros de listado (busqueda, status) en organizaciones.
- Visibilidad de organizaciones segun membresia.

## Verificacion final

```bash
cd app
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose exec app php artisan route:list --except-vendor
```

Resultado:
- Migraciones fresh + seed: OK (admin@clientflow.test, cliente@clientflow.test, org demo).
- 72 tests pasan.
- Build Vite sin warnings.
- 26 rutas custom.

## Decisiones tecnicas relevantes

1. **Slug generado en `creating`**: la generacion automatica evita
   inconsistencias entre la URL y el nombre. `firstOrCreate` con
   columna generada funciona correctamente cuando se llama desde
   tinker, pero el seeder prefijere generar el slug explicitamente
   para evitar el camino de busqueda + creacion cuando se conoce
   el nombre de antemano.

2. **Tokens de invitacion hasheados**: el token crudo solo se
   entrega en el email. En BD se guarda `Hash::make($rawToken)`, y
   la busqueda se hace comparando contra el hash. Esto evita que
   un dump de la BD permita aceptar invitaciones ajenas.

3. **Middleware con redireccion en vez de 403**: se considera
   "despiste temporal" del usuario (enlace antiguo, marcador). Se
   le guia a su dashboard en lugar de mostrarle una pantalla de
   error.

4. **Policies incluso detras de middleware**: aunque el middleware
   `admin` ya bloquea el acceso, las policies mantienen defensa en
   profundidad. Si en una fase futura se anade una ruta mixta, las
   policies seguiran garantizando el aislamiento.

5. **Idempotencia en `accept`**: aceptar dos veces la misma
   invitacion no produce duplicados en `organization_user`. Esto
   cubre el caso de doble clic o reintento tras timeout.

6. **Mensaje generico en recuperacion de contrasena**: aunque
   internamente sepamos si el email existe, al usuario siempre se
   le muestra el mismo mensaje. Es un estandar de seguridad para
   no filtrar que cuentas estan registradas.

## Pendiente (fuera de scope de fase 1)

- Vistas `/portal/organizations/{org}` para que el cliente vea sus
  organizaciones en el portal (ahora solo ve el listado desde el
  dashboard).
- Reenviar invitacion y revocar invitacion (estan en
  `OrganizationMemberController` como rutas planeadas).
- Verificacion de email (la migracion `0001_01_01_000000_create_users_table`
  ya incluye `email_verified_at`).
- Avatar upload y gestion de logo de organizacion.
- i18n real (ahora los strings viven en castellano hard-coded).

## Riesgos y notas para la fase 2

- La relacion `User::projects()` esta declarada pero el modelo
  `Project` no existe. Llegara en la fase 2; mientras tanto el
  compilador no se queja por las declaraciones con type-hints.
- `Organization::projects()` y `Organization::invitations()` son
  declaraciones adelantadas equivalentes. La primera no se usa
  todavia; la segunda ya esta en uso via `$organization->pendingInvitations()`.
- `EnsureUserIsAdmin` y `EnsureUserIsClient` redirigen en vez de
  abortar con 403. Si en una fase futura se necesita un 403
  explicito para integraciones externas (MCP server, por ejemplo),
  habra que introducir un middleware paralelo o cambiar el
  comportamiento.
