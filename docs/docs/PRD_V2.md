# PRD_V2 — ClientFlow

## 1. Visión

ClientFlow es una plataforma web open source para freelancers, consultores, estudios pequeños y agencias que quieren ofrecer a sus clientes una experiencia premium de seguimiento de proyectos.

El producto se inspira en los talleres modernos que documentan el avance de una reparación con fotos, vídeos, explicaciones sencillas y estados claros. Aplicado a proyectos digitales, ClientFlow permite que un cliente vea avances, vídeos, entregables, comentarios, aprobaciones, documentos y próximos hitos desde un único portal privado.

> Tus clientes no vuelven a preguntarte cómo va el proyecto, porque pueden verlo en tiempo real.

## 2. Problema

En muchos proyectos pequeños y medianos, la comunicación con el cliente ocurre de forma desordenada: WhatsApp, emails dispersos, llamadas sin registro, capturas sueltas, documentos enviados varias veces, cambios aprobados de palabra y falta de visibilidad entre entregas.

Esto genera ansiedad en el cliente y pérdida de tiempo en el profesional.

### Problemas del cliente

- No sabe qué se ha hecho.
- No sabe qué falta por hacer.
- No sabe qué está pendiente de su parte.
- No sabe cuándo tendrá una nueva entrega.
- No encuentra documentos.
- No tiene claro qué cambios pidió.
- No sabe qué entregables ha aprobado.

### Problemas del administrador/freelancer

- Repite explicaciones de estado.
- Busca archivos enviados.
- Responde mensajes repetitivos.
- Traduce lenguaje técnico a lenguaje cliente.
- Documenta avances manualmente.
- Pide aprobaciones sin trazabilidad.
- Recuerda decisiones tomadas en canales externos.

## 3. Solución

ClientFlow centraliza la relación del proyecto en un portal privado. Cada cliente tiene acceso a su propio espacio, donde puede ver estado general, progreso, fase actual, próximo hito, últimas actualizaciones, diario visual, documentos, entregables, comentarios, solicitudes pendientes y aprobaciones.

El administrador dispone de un panel para gestionar clientes, proyectos, actualizaciones, archivos, entregables, comentarios, notificaciones y contenido generado con IA.

## 4. Diferenciación

ClientFlow no compite directamente con Jira, Trello, Asana, ClickUp o Notion. Esas herramientas están orientadas al equipo interno. ClientFlow está orientado a la experiencia del cliente final.

| Diferenciador | Descripción |
|---|---|
| Diario visual | Avances explicados mediante vídeos, capturas, notas y documentos. |
| Lenguaje cliente | El foco está en explicar el estado sin tecnicismos. |
| Portal privado | Cada cliente tiene un espacio claro y controlado. |
| Aprobaciones | Los entregables pueden aprobarse o requerir cambios. |
| Transparencia | El cliente ve progreso, hitos y bloqueos. |
| IA útil | La IA transforma notas internas en actualizaciones entendibles. |
| Open source | Puede ser clonado, adaptado y usado en hosting propio. |

## 5. Público objetivo

### Usuario principal

Freelancer, consultor, desarrollador independiente o pequeña agencia que presta servicios digitales.

Ejemplos: desarrollador web, especialista en automatizaciones IA, consultor tecnológico, diseñador web, agencia de marketing pequeña, estudio de software, implementador de CRMs, creador de automatizaciones n8n, consultor SEO o agencia ecommerce.

### Cliente final

Empresa o persona que contrata un proyecto.

Ejemplos: clínica que contrata una web, restaurante que contrata una automatización de reservas, empresa que contrata un chatbot, academia que contrata una plataforma o pyme que contrata una integración con WhatsApp.

## 6. Posicionamiento

ClientFlow debe sentirse como un portal premium, una herramienta sencilla, un espacio privado, un sistema de confianza y una experiencia profesional.

No debe sentirse como Jira, un CRM complejo, un panel técnico, una intranet antigua, una plantilla genérica de Tailwind o un gestor de tareas lleno de columnas.

## 7. Principios de producto

### Claridad antes que complejidad

El cliente debe entender el estado del proyecto en menos de 10 segundos.

### Visual antes que textual

Una actualización con vídeo o captura puede valer más que un párrafo largo.

### Trazabilidad sin fricción

Las decisiones importantes deben quedar registradas, pero sin convertir la plataforma en una herramienta pesada.

### Lenguaje humano

Los textos visibles por el cliente deben evitar tecnicismos innecesarios.

### Admin rápido

El administrador debe poder publicar una actualización en menos de 2 minutos.

### IA asistida

La IA no toma decisiones ni publica sola. Ayuda a redactar, resumir, traducir lenguaje técnico y preparar informes.

## 8. Roles

### Administrador

Puede gestionar clientes, crear cuentas, invitar por enlace, crear proyectos, publicar actualizaciones, subir vídeos, imágenes y documentos, gestionar entregables, responder comentarios, solicitar aprobaciones, generar textos con IA, revisar actividad y configurar el portal.

### Cliente

Puede ver sus proyectos, consultar estado y progreso, ver timeline, ver diario visual, descargar documentos, comentar, aprobar entregables, solicitar cambios, editar su perfil y ver notificaciones.

### Futuro: colaborador interno

Podría crear actualizaciones, subir archivos, responder comentarios y gestionar tareas internas limitadas. No entra en el MVP.

## 9. MVP funcional

### Autenticación

- Login.
- Registro público opcional.
- Registro mediante invitación.
- Recuperación de contraseña.
- Cierre de sesión.
- Redirección por rol.

### Gestión de clientes

- Listado de clientes.
- Crear cliente manualmente.
- Crear cuenta de usuario asociada.
- Invitar cliente por email.
- Copiar enlace de invitación.
- Ver detalle de cliente.
- Editar cliente.
- Activar/desactivar cliente.
- Ver proyectos asociados.

### Gestión de proyectos

- Listado de proyectos.
- Crear proyecto.
- Asignar cliente.
- Definir nombre, descripción, estado, progreso, fase, fechas e imagen.
- Editar proyecto.
- Archivar proyecto.
- Ver dashboard interno del proyecto.

### Estado del proyecto

Cada proyecto debe mostrar estado, porcentaje de progreso, fase actual, próximo hito, fecha estimada de entrega, última actualización y bloqueos si existen.

### Timeline

El timeline muestra eventos del proyecto ordenados por fecha: inicio, actualización publicada, documento subido, entregable creado, comentario del cliente, entregable aprobado, solicitud de cambios y cambio de estado.

### Diario visual

Módulo diferencial. Permite publicar entradas con título, descripción, tipo, visibilidad, adjuntos, estado y etiquetas opcionales.

Tipos: vídeo demo, captura comentada, audio explicativo, nota de avance, comparativa antes/después, evidencia de prueba y bloqueo explicado.

### Documentos

Permite organizar archivos por proyecto: contrato, presupuesto, factura, manual, entregable, capturas, material del cliente y otros.

### Entregables

Un entregable representa algo que el cliente debe revisar. Estados: borrador, enviado, en revisión, aprobado, cambios solicitados y cerrado.

### Comentarios

Comentarios asociados a proyecto, actualización, entregable o documento. Deben permitir responder, marcar como resuelto, ver autor y fecha.

### Notificaciones

MVP por email. Eventos: invitación creada, nueva actualización, nuevo documento, entregable pendiente, comentario recibido, entregable aprobado y cambios solicitados.

### IA

Primera versión simple y útil:

- Generar resumen para cliente a partir de notas internas.
- Reescribir actualización en lenguaje no técnico.
- Generar resumen semanal de proyecto.
- Preparar texto de email de notificación.
- Generar checklist de revisión de entregable.

Integración recomendada: Laravel llama a un webhook de n8n, n8n llama a Gemini, Laravel recibe respuesta y la muestra para revisión. La IA nunca publica automáticamente en el MVP.

## 10. Funcionalidades futuras

### V1.1

- Plantillas de proyecto.
- Plantillas de actualizaciones.
- Menciones.
- Filtros avanzados.
- Vista calendario.
- Exportar informe PDF.
- Marca blanca básica.

### V1.2

- Integración GitHub/GitLab.
- Resumen automático a partir de commits.
- Webhooks externos.
- WhatsApp vía proveedor externo.
- Grabación directa de vídeo desde navegador.

### V2

- Multiempresa.
- Suscripciones.
- Roles avanzados.
- Equipos internos.
- Portal público de estado.
- API externa.
- Marketplace de plantillas.

## 11. Qué NO entra en el MVP

No entra inicialmente: Kanban avanzado, Scrum, sprints, control horario, facturación completa, CRM comercial, firma digital, videollamadas, chat en tiempo real, app móvil, multiempresa SaaS, integración ERP o automatizaciones complejas sin n8n.

## 12. Estados

### Proyecto

| Estado | Uso |
|---|---|
| Borrador | Creado pero no visible. |
| Planificación | Definiendo alcance y pasos. |
| En progreso | Trabajo activo. |
| Esperando cliente | Falta feedback, documento o aprobación. |
| En revisión | Cliente debe revisar algo. |
| Pausado | Proyecto detenido temporalmente. |
| Finalizado | Proyecto cerrado. |
| Archivado | Oculto de vistas principales. |

### Entregable

| Estado | Uso |
|---|---|
| Borrador | No visible para cliente. |
| Enviado | Cliente ya puede verlo. |
| En revisión | Cliente lo está revisando. |
| Aprobado | Cliente acepta entrega. |
| Cambios solicitados | Cliente pide cambios. |
| Cerrado | Entregable terminado. |

## 13. Experiencia admin

El administrador debe sentir que el panel le ahorra trabajo. Acciones rápidas prioritarias: crear cliente, crear proyecto, publicar actualización, subir vídeo/captura, solicitar aprobación, responder comentario y generar resumen IA.

El dashboard admin debe mostrar proyectos activos, proyectos esperando cliente, entregables pendientes, comentarios sin responder, últimas actividades y accesos rápidos.

## 14. Experiencia cliente

El cliente debe sentir tranquilidad. El dashboard cliente debe responder: cómo va mi proyecto, qué se ha hecho, qué falta, qué necesita mi aprobación y dónde están los documentos.

## 15. Métricas de éxito

### Para administrador

- Menos mensajes preguntando por estado.
- Menos emails manuales.
- Menos archivos perdidos.
- Más aprobaciones registradas.
- Menos malentendidos.
- Más sensación de control.

### Para cliente

- Mayor confianza.
- Mayor claridad.
- Mayor percepción de profesionalidad.
- Mejor seguimiento.
- Menor ansiedad entre entregas.

### Para open source

- Instalaciones.
- Stars en GitHub.
- Issues útiles.
- Contribuciones.
- Casos reales de uso.
- Forks.

## 16. Criterios de aceptación del MVP

El MVP estará listo cuando se pueda hacer el flujo completo:

1. Admin entra.
2. Crea cliente manualmente.
3. Crea proyecto para ese cliente.
4. Publica actualización con texto y captura.
5. Publica vídeo en diario visual.
6. Sube documento visible.
7. Crea entregable.
8. Cliente entra.
9. Cliente ve estado del proyecto.
10. Cliente consulta timeline y diario visual.
11. Cliente descarga documento.
12. Cliente comenta.
13. Cliente aprueba o solicita cambios.
14. Admin responde.
15. Sistema registra actividad.
16. Admin genera resumen con IA y lo edita antes de publicar.

## 17. Frase de producto

> El portal privado donde tus clientes ven, entienden y aprueban el avance de sus proyectos.
