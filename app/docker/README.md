# Docker local para ClientFlow

Este entorno no usa Laravel Sail.

## Servicios

- `app`: PHP 8.4 FPM con Composer y extensiones Laravel/MySQL.
- `nginx`: servidor web en `http://localhost:8080`.
- `mysql`: MySQL 8.4 expuesto localmente en `127.0.0.1:3307`.
- `node`: Vite en `http://localhost:5173`.

## Arranque

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

## Comandos utiles

```bash
docker compose exec app php artisan test
docker compose exec app php artisan migrate:fresh
docker compose exec app composer require vendor/package
docker compose logs -f app
```

## Base de datos

- Host desde Laravel: `mysql`
- Host desde la maquina local: `127.0.0.1`
- Puerto local: `3307`
- Base de datos: `clientflow`
- Usuario: `clientflow`
- Password: `clientflow`
