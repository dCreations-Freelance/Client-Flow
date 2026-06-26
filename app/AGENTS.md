# Instrucciones para agents de opencode

## Tests

- **PHPUnit**: `php vendor/bin/phpunit` (sin Docker) o `docker compose exec app php vendor/bin/phpunit`
- **Playwright E2E**: tests en `tests/e2e/`. Ejecutar con `docker compose run --rm playwright npx playwright test`
  - La app se accede via `http://nginx:80` en la red Docker
  - Usar `npx playwright codegen http://localhost:8080` en local (fuera de Docker) para grabar interacciones

## HyperFrames + Playwright

Para grabar la aplicación y extraer capturas / assets visuales para videos HyperFrames:

1. Arrancar la app: `docker compose up -d`
2. Usar Playwright para navegar, screenshots o grabar video:
   - `npx playwright screenshot http://localhost:8080/admin/tablero screen.png`
   - `npx playwright codegen http://localhost:8080` para grabar interacciones
   - Desde Docker: `docker compose run --rm playwright npx playwright screenshot http://nginx:80 /var/www/html/screen.png`
3. Las capturas pueden usarse como素材 en composiciones HyperFrames

## Docker

- `docker compose up -d` — arranca todos los servicios
- `docker compose down` — para todo
- Servicios: app (PHP), nginx (web :8080), mysql (BD :3307), node (Vite :5173), playwright (solo con profile `test`)
- Playwright solo arranca con: `docker compose run --rm playwright <comando>`
