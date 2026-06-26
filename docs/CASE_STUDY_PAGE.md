# Plantilla de página de caso de estudio

Documento de referencia para construir páginas de caso de estudio premium (proyectos, productos, MVPs) en ClientFlow.

---

## 1. Propósito

Estandarizar la estructura, el contenido y el proceso de generación de páginas de caso de estudio, de modo que cualquier proyecto pueda presentarse de forma consistente, persuasiva y técnicamente honesta.

---

## 2. Estructura recomendada de la página

### 2.1 Hero

- Título del proyecto.
- Subtítulo con el problema o el resultado.
- CTA principal: ver demo, ver video o ver código.
- CTA secundario: contacto o siguiente proyecto.

### 2.2 Media principal

- Video corto en autoplay o click-to-play.
- Una captura principal o mockup destacado.
- Texto breve explicando lo que se ve.

### 2.3 Ficha rápida

Bloque con datos clave:

- Rol.
- Año.
- Tipo de proyecto.
- Stack principal.
- Duración.
- Estado.
- Enlaces relevantes.

### 2.4 Contexto

- Qué problema existía.
- Quién lo necesitaba.
- Por qué era importante resolverlo.

### 2.5 Solución

- Qué se construyó.
- Qué enfoque se tomó.
- Qué partes del producto resuelven el problema.

### 2.6 Features principales

Para cada feature:

- Nombre.
- Objetivo.
- Comportamiento.
- Valor aportado.
- Captura o video corto.

### 2.7 Galería con contexto

No solo mostrar imágenes. Cada captura debe incluir:

- Título.
- Descripción corta.
- Qué problema o flujo muestra.

### 2.8 Video walkthrough

- Video más largo si el proyecto lo justifica.
- Recorrido general del producto.
- Puede incluir narración o subtítulos.

### 2.9 Tecnologías

Por cada tecnología importante:

- Qué hace en el proyecto.
- Por qué se eligió.
- Qué parte cubre.

### 2.10 Arquitectura

Debe explicar claramente:

- Frontend.
- Backend.
- CMS si existe.
- APIs.
- Base de datos.
- Servicios externos.
- Flujo de datos.

Idealmente incluir:

- Diagrama simple.
- Explicación de capas.
- Decisiones técnicas relevantes.

### 2.11 Proceso

- Discovery.
- Investigación.
- Wireframes.
- Iteraciones.
- Implementación.
- Validación.

### 2.12 Retos y decisiones

- Problemas encontrados.
- Soluciones aplicadas.
- Compromisos o tradeoffs.

### 2.13 Resultados

- Métricas si existen.
- Mejoras de rendimiento.
- Mejoras de UX.
- Impacto real.

### 2.14 Aprendizajes

- Qué funcionó bien.
- Qué mejoraría.
- Qué se aprendió.

### 2.15 Cierre

- Resumen final.
- CTA a contacto, demo o siguiente proyecto.

---

## 3. Material que debe preparar la IA

La IA que analice el proyecto debe devolver, como mínimo:

- Resumen ejecutivo.
- Problema.
- Solución.
- Público objetivo.
- Funcionalidades.
- Stack.
- Arquitectura.
- Flujo de usuario.
- Capturas sugeridas.
- Videos sugeridos.
- Textos para cada sección.
- Datos faltantes.
- Riesgos o dudas abiertas.

---

## 4. Prompt para la IA que analiza el proyecto

```text
Actúa como analista de producto, arquitecto técnico y redactor de casos de estudio.

Tu tarea es analizar un proyecto completo y devolver toda la información necesaria para crear una página de caso de estudio premium.

Objetivo:
- entender el proyecto de principio a fin;
- extraer su valor real;
- identificar su arquitectura técnica;
- proponer la estructura de contenido ideal para la página;
- preparar material útil para que otra IA genere después el componente .astro.

Instrucciones:
- No te limites a resumir.
- Analiza el proyecto desde producto, negocio, técnica, UX y contenido.
- Si falta información, indícala claramente como pendiente o inferida.
- No inventes datos reales; si algo no se puede confirmar, márcalo como supuesto.
- Devuelve el resultado en markdown limpio y estructurado.

Necesito que entregues estas secciones:

1. Visión general
- Qué es el proyecto.
- Para quién es.
- Qué problema resuelve.
- Cuál es su propuesta de valor.

2. Resumen ejecutivo
- 5 a 8 bullets con lo más importante.

3. Contexto de negocio
- Situación inicial.
- Necesidad principal.
- Objetivo del proyecto.

4. Público objetivo
- Usuarios principales.
- Necesidades y expectativas.

5. Funcionalidades clave
- Lista de features.
- Explicación breve de cada una.

6. Flujo de usuario
- Cómo se usa el proyecto paso a paso.
- Casos de uso principales.

7. Arquitectura técnica
- Frontend.
- Backend.
- Base de datos.
- APIs.
- Integraciones externas.
- Autenticación si existe.
- Flujo de datos.

8. Tecnologías usadas
- Lista completa.
- Motivo de elección.
- Papel de cada tecnología.

9. UI/UX y presentación visual
- Patrones de interfaz.
- Decisiones visuales.
- Componentes o pantallas relevantes.

10. Contenido visual necesario
- Capturas necesarias.
- Videos necesarios.
- Diagramas necesarios.
- Mockups recomendados.

11. Retos y decisiones
- Problemas técnicos o de producto.
- Soluciones aplicadas.
- Tradeoffs.

12. Resultados e impacto
- Métricas si existen.
- Impacto esperado o real.

13. Aprendizajes
- Lo aprendido.
- Mejoras futuras.

14. Dudas y datos faltantes
- Lista de información que falta para cerrar la página.

15. Material listo para la página
- Hero copy.
- Subtítulo.
- Texto de contexto.
- Texto de solución.
- Títulos de secciones.
- Copy breve para features.
- Copy para tecnología y arquitectura.

Formato de salida:
- Usa encabezados claros.
- Usa tablas solo si aportan claridad.
- Mantén frases concretas.
- Incluye bullets donde ayuden.
- Si puedes, termina con una lista de campos estructurados en JSON o YAML para facilitar la generación posterior.
```
