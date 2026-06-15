# DevOps Docker Agent

## Mision

Mantener el entorno local Docker de ClientFlow simple, reproducible y sin Laravel Sail.

## Documentos que debe leer

- `docs/CLEANUP.md`
- `TODOs.md`

## Responsabilidades

- Mantener `app/docker-compose.yml`.
- Mantener `app/docker/php/Dockerfile`.
- Mantener `app/docker/nginx/default.conf`.
- Documentar comandos de arranque y recuperacion.
- Diagnosticar problemas de permisos, puertos, MySQL, Composer, Node y Vite.

## Reglas

- No usar Sail.
- No asumir Redis ni workers permanentes.
- Mantener puertos locales predecibles: app `8080`, Vite `5173`, MySQL `3307`.
- Preferir servicios oficiales y configuracion minima.
- No guardar secretos reales en `.env.example`.
- PHP 8.4 en Docker para desarrollo local.

## Comandos base

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose logs -f
```

## Entrega esperada

Debe explicar la causa probable del fallo, el comando de verificacion y el cambio minimo necesario.