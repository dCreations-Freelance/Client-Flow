# ClientFlow

ClientFlow es un portal open source para freelancers, consultores y pequeñas agencias que quieren ofrecer a sus clientes una experiencia premium de seguimiento de proyectos.

No es otro gestor de tareas. Es un espacio privado donde el cliente puede ver el estado real del proyecto, consultar avances visuales, descargar documentos, comentar, revisar entregables y aprobar hitos importantes.

## Propuesta de valor

La mayoría de clientes no quieren aprender Jira, Trello o ClickUp. Quieren saber cómo va su proyecto, qué se ha hecho, qué falta, qué tienen pendiente, dónde están los documentos y qué deben aprobar.

ClientFlow resuelve eso con un portal claro, visual y profesional.

## Funcionalidades principales

- Portal privado para clientes.
- Dashboard de proyectos.
- Estado visual de progreso.
- Timeline de actividad.
- Diario visual con vídeos, capturas, audios y notas.
- Documentos centralizados.
- Entregables con aprobación.
- Comentarios por proyecto.
- Notificaciones por email.
- Centro IA para resumir y reescribir avances.
- Integración con n8n/Gemini mediante webhooks.
- Panel de administración.
- Código open source.

## Stack previsto

- Laravel.
- Blade.
- Livewire.
- Alpine.js.
- Tailwind CSS.
- MySQL.
- SMTP.
- Storage local.
- n8n opcional para IA.
- Gemini opcional para generación de textos.

## Enfoque de hosting

ClientFlow está pensado para poder arrancar en hosting compartido. Por eso el MVP evita Redis obligatorio, workers permanentes, Docker en producción, microservicios e infraestructura compleja.

## Documentación incluida

```txt
docs/
├── PRD_V2.md
├── USER_FLOW_MASTER.md
├── DESIGN.md
├── WIREFRAMES_DESKTOP.md
├── ARCHITECTURE_V2.md
└── IMPLEMENTATION_STARTER.md
```

## Roadmap

### MVP

- Auth.
- Roles admin/cliente.
- Clientes.
- Proyectos.
- Timeline.
- Actualizaciones.
- Documentos.
- Comentarios.
- Diario visual.
- Entregables.
- Aprobaciones.

### V1

- Notificaciones completas.
- IA mediante n8n/Gemini.
- Actividad global.
- Ajustes del portal.
- Plantillas básicas.

### V2

- Multiempresa.
- Marca blanca.
- Integración GitHub/GitLab.
- Informes PDF.
- Webhooks públicos.

## Filosofía

ClientFlow existe para reducir ansiedad, mejorar comunicación y aumentar percepción de profesionalidad. Una buena plataforma de cliente no debe obligar al cliente a gestionar el proyecto. Debe darle tranquilidad.

## Licencia

MIT.
