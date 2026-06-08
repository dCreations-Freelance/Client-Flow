# USER_FLOW_MASTER — Mapa completo de pantallas y flujos

## 1. Objetivo

Este documento define el mapa completo de pantallas y flujos de ClientFlow para escritorio.

Incluye flujos públicos, autenticación, administrador, cliente, estados vacíos, estados de error, acciones principales por pantalla y relación entre pantallas.

## 2. Roles

### Visitante

Puede ver landing, registrarse si el registro público está activo, aceptar invitación, iniciar sesión y recuperar contraseña.

### Administrador

Puede gestionar clientes, proyectos, actualizaciones, diario visual, documentos, entregables, comentarios, IA y ajustes.

### Cliente

Puede ver sus proyectos, consultar avances, comentar, descargar documentos, aprobar entregables, solicitar cambios y gestionar su perfil.

## 3. Mapa global de pantallas

```txt
PUBLIC
├── 00 Landing
├── 01 Login
├── 02 Registro público
├── 03 Aceptar invitación
├── 04 Recuperar contraseña
├── 05 Crear nueva contraseña
├── 06 Registro completado
├── 07 Acceso denegado
└── 08 Página no encontrada

ADMIN
├── 09 Dashboard admin
├── 10 Clientes - listado
├── 11 Clientes - crear manualmente
├── 12 Clientes - invitar por email
├── 13 Clientes - detalle
├── 14 Clientes - editar
├── 15 Clientes - estado vacío
├── 16 Proyectos - listado
├── 17 Proyectos - crear
├── 18 Proyectos - detalle/dashboard
├── 19 Proyectos - editar configuración
├── 20 Proyectos - timeline
├── 21 Proyectos - nueva actualización
├── 22 Proyectos - editar actualización
├── 23 Diario visual - listado
├── 24 Diario visual - nueva entrada
├── 25 Diario visual - detalle entrada
├── 26 Documentos - listado
├── 27 Documentos - subir documento
├── 28 Entregables - listado
├── 29 Entregables - crear
├── 30 Entregables - detalle
├── 31 Comentarios - bandeja
├── 32 Comentarios - detalle hilo
├── 33 Centro IA
├── 34 IA - generar resumen
├── 35 IA - historial de generaciones
├── 36 Notificaciones admin
├── 37 Actividad global
├── 38 Ajustes - perfil
├── 39 Ajustes - portal
├── 40 Ajustes - emails
├── 41 Ajustes - IA/n8n
└── 42 Ajustes - seguridad

CLIENTE
├── 43 Dashboard cliente
├── 44 Mis proyectos
├── 45 Proyecto cliente - vista general
├── 46 Proyecto cliente - timeline
├── 47 Proyecto cliente - diario visual
├── 48 Proyecto cliente - documentos
├── 49 Proyecto cliente - entregables
├── 50 Cliente - detalle entregable
├── 51 Cliente - solicitar cambios
├── 52 Cliente - aprobar entregable
├── 53 Cliente - comentarios
├── 54 Cliente - nuevo comentario
├── 55 Cliente - notificaciones
├── 56 Cliente - perfil
├── 57 Cliente - estado vacío sin proyectos
└── 58 Cliente - proyecto sin acceso
```

## 4. Flujos públicos

### Landing a registro público

```txt
00 Landing
   └── Crear cuenta
        └── 02 Registro público
             ├── Validación correcta
             │    └── 06 Registro completado
             │         └── 43 Dashboard cliente
             └── Error validación
                  └── 02 Registro público con errores
```

Uso: cuando el administrador envía la URL del portal para que el cliente se registre.

### Landing a login

```txt
00 Landing
   └── Acceder
        └── 01 Login
             ├── Usuario admin
             │    └── 09 Dashboard admin
             ├── Usuario cliente
             │    └── 43 Dashboard cliente
             └── Error
                  └── 01 Login con error
```

### Invitación por email

```txt
Email de invitación
   └── Link con token
        └── 03 Aceptar invitación
             ├── Token válido
             │    └── Crear contraseña
             │         └── 06 Registro completado
             │              └── 43 Dashboard cliente
             └── Token inválido/caducado
                  └── 07 Acceso denegado
```

## 5. Flujos administrador

### 5.1 Dashboard admin

Pantalla: `09 Dashboard admin`.

El administrador ve proyectos activos, proyectos esperando cliente, comentarios sin responder, entregables pendientes, última actividad y accesos rápidos.

Acciones: crear cliente, invitar cliente, crear proyecto, publicar actualización, ir a comentarios, ir a entregables y generar resumen IA.

### 5.2 Crear cliente manualmente

```txt
09 Dashboard admin
   └── Nuevo cliente
        └── 11 Clientes - crear manualmente
             ├── Guardar sin usuario
             │    └── 13 Clientes - detalle
             ├── Guardar y crear usuario
             │    └── Sistema genera cuenta
             │         └── Email con contraseña temporal
             │              └── 13 Clientes - detalle
             └── Cancelar
                  └── 10 Clientes - listado
```

Campos: nombre, empresa, email, teléfono, notas internas, crear acceso al portal y enviar email de bienvenida.

### 5.3 Invitar cliente

```txt
10 Clientes - listado
   └── Invitar cliente
        └── 12 Clientes - invitar por email
             ├── Enviar invitación
             │    └── Cliente recibe enlace
             │         └── 03 Aceptar invitación
             └── Copiar enlace manualmente
                  └── Admin lo envía por WhatsApp/email
```

Campos: nombre, email, empresa opcional, proyecto asociado opcional y mensaje personalizado.

### 5.4 Crear proyecto

```txt
09 Dashboard admin
   └── Nuevo proyecto
        └── 17 Proyectos - crear
             ├── Seleccionar cliente existente
             ├── Crear cliente rápido
             ├── Definir datos
             └── Guardar
                  └── 18 Proyectos - detalle/dashboard
```

Campos: cliente, nombre, descripción, objetivo, estado, fase, progreso, fecha inicio, fecha estimada, visibilidad e imagen.

### 5.5 Gestionar proyecto

Pantalla: `18 Proyectos - detalle/dashboard`.

Secciones: resumen, estado, progreso, próximo hito, timeline reciente, diario visual reciente, entregables pendientes, documentos, comentarios y actividad interna.

Acciones: editar proyecto, nueva actualización, nueva entrada visual, subir documento, crear entregable, solicitar aprobación, generar resumen IA, cambiar estado y archivar.

### 5.6 Publicar actualización

```txt
18 Proyecto
   └── Nueva actualización
        └── 21 Proyectos - nueva actualización
             ├── Escribir manualmente
             ├── Adjuntar archivos
             ├── Marcar visibilidad
             ├── Generar resumen IA
             ├── Guardar borrador
             └── Publicar
                  ├── Aparece en timeline
                  └── Cliente recibe notificación
```

### 5.7 Diario visual

```txt
18 Proyecto
   └── Nueva entrada visual
        └── 24 Diario visual - nueva entrada
             ├── Subir vídeo
             ├── Subir imagen
             ├── Subir audio
             ├── Escribir nota
             ├── Generar explicación IA
             └── Publicar
                  └── 47 Proyecto cliente - diario visual
```

Tipos: vídeo demo, captura comentada, audio explicativo, nota de avance, comparativa antes/después, evidencia de prueba y bloqueo explicado.

### 5.8 Documentos

```txt
18 Proyecto
   └── Documentos
        ├── 26 Documentos - listado
        ├── 27 Documentos - subir documento
        ├── Editar visibilidad
        ├── Descargar
        └── Eliminar
```

Categorías: presupuesto, contrato, factura, manual, entregable, captura, material del cliente y otros.

### 5.9 Entregables

```txt
18 Proyecto
   └── Entregables
        ├── 28 Entregables - listado
        ├── 29 Entregables - crear
        └── 30 Entregables - detalle
             ├── Enviar a revisión
             ├── Cliente aprueba
             ├── Cliente solicita cambios
             └── Cerrar entregable
```

### 5.10 Comentarios

```txt
31 Comentarios - bandeja
   ├── Filtrar por proyecto
   ├── Filtrar por cliente
   ├── Filtrar sin responder
   └── 32 Comentarios - detalle hilo
        ├── Responder
        ├── Marcar como resuelto
        └── Crear actualización desde comentario
```

### 5.11 Centro IA

```txt
33 Centro IA
   ├── 34 IA - generar resumen
   ├── Reescribir actualización
   ├── Crear informe semanal
   ├── Convertir lenguaje técnico
   ├── Generar email de aviso
   └── 35 IA - historial
```

La IA siempre devuelve borradores editables.

## 6. Flujos cliente

### 6.1 Dashboard cliente

Pantalla: `43 Dashboard cliente`.

El cliente ve proyectos activos, estado de cada proyecto, próximo hito, pendientes de revisión, últimas actualizaciones, documentos recientes y notificaciones.

Acciones: abrir proyecto, ver entregable pendiente, comentar, descargar documento y aprobar.

### 6.2 Ver proyecto

```txt
43 Dashboard cliente
   └── Click proyecto
        └── 45 Proyecto cliente - vista general
             ├── Timeline
             ├── Diario visual
             ├── Documentos
             ├── Entregables
             └── Comentarios
```

La vista general debe responder: estado actual, progreso, qué se ha hecho, qué falta, qué necesita el cliente y próxima fecha relevante.

### 6.3 Timeline cliente

```txt
45 Proyecto cliente
   └── Timeline
        └── 46 Proyecto cliente - timeline
             ├── Ver actualización
             ├── Descargar adjunto
             └── Comentar actualización
```

### 6.4 Diario visual cliente

```txt
45 Proyecto cliente
   └── Diario visual
        └── 47 Proyecto cliente - diario visual
             ├── Reproducir vídeo
             ├── Ver captura
             ├── Escuchar audio
             └── Comentar entrada
```

### 6.5 Documentos cliente

```txt
45 Proyecto cliente
   └── Documentos
        └── 48 Proyecto cliente - documentos
             ├── Filtrar por categoría
             ├── Descargar
             └── Ver detalle
```

### 6.6 Entregables cliente

```txt
45 Proyecto cliente
   └── Entregables
        └── 49 Proyecto cliente - entregables
             └── 50 Cliente - detalle entregable
                  ├── Aprobar
                  │    └── 52 Cliente - aprobar entregable
                  └── Solicitar cambios
                       └── 51 Cliente - solicitar cambios
```

### 6.7 Aprobar entregable

```txt
50 Detalle entregable
   └── Aprobar
        └── 52 Confirmación aprobación
             ├── Añadir comentario opcional
             └── Confirmar
                  └── Estado: Aprobado
```

### 6.8 Solicitar cambios

```txt
50 Detalle entregable
   └── Solicitar cambios
        └── 51 Solicitar cambios
             ├── Escribir cambios
             ├── Adjuntar archivo opcional
             └── Enviar
                  └── Estado: Cambios solicitados
```

### 6.9 Comentarios cliente

```txt
45 Proyecto cliente
   └── Comentarios
        └── 53 Cliente - comentarios
             ├── Ver hilos
             ├── Responder
             └── 54 Nuevo comentario
```

## 7. Estados vacíos

### Admin sin clientes

Pantalla: `15 Clientes - estado vacío`.

Mensaje: “Todavía no tienes clientes. Crea tu primer cliente o envía una invitación para empezar.”

Acciones: crear cliente e invitar cliente.

### Admin sin proyectos

Mensaje: “Crea tu primer proyecto para empezar a compartir avances con un cliente.”

Acciones: nuevo proyecto o crear cliente primero.

### Cliente sin proyectos

Pantalla: `57 Cliente - estado vacío sin proyectos`.

Mensaje: “Tu cuenta ya está lista. Cuando tu proyecto esté disponible aparecerá aquí.”

Acción: contactar con el administrador.

## 8. Estados de error

### Sin permisos

Pantalla: `07 Acceso denegado`.

Causas: proyecto no asociado al cliente, documento privado, token caducado o usuario desactivado.

### Página no encontrada

Pantalla: `08 Página no encontrada`.

Debe permitir volver al dashboard según rol.

## 9. Navegación admin

```txt
Sidebar Admin
├── Dashboard
├── Clientes
├── Proyectos
├── Comentarios
├── Entregables
├── Diario visual
├── Documentos
├── Centro IA
├── Actividad
└── Ajustes
```

## 10. Navegación cliente

```txt
Sidebar Cliente
├── Inicio
├── Mis proyectos
├── Documentos
├── Entregables
├── Comentarios
├── Notificaciones
└── Perfil
```

## 11. Acciones rápidas admin

Nuevo cliente, invitar cliente, nuevo proyecto, nueva actualización, nueva entrada visual, subir documento, crear entregable y generar resumen IA.

## 12. Acciones rápidas cliente

Ver proyecto, aprobar entregable, solicitar cambios, nuevo comentario y descargar documento.
