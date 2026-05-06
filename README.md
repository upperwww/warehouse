# Warehouse

Laravel Livewire warehouse app for tracking stone materials and slabs.

## What Is Built

- Login screen with a seeded admin user.
- Warehouse dashboard with stock counts and status overview.
- Materials CRUD.
- Slabs CRUD.
- Slab search and filters by material/status.
- Bundle auto-loading for routes, migrations, Livewire components, and namespaced views.

## Architecture Notes

The assignment requires `Services` and `Repositories` folders inside the bundle, so they exist.

They are empty on purpose.

No Pointless Middleman classes were added. Eloquent already handles normal database work cleanly, and the Livewire components are small enough to read without opening five files to find one query. A repository that only calls `Material::query()` is not architecture. It is a toll booth.

## Bundle Structure

```text
app/Bundles/Warehouse
+-- Livewire
+-- Migrations
+-- Models
+-- Repositories
+-- Routes
+-- Services
+-- Utils
```

Views live in:

```text
resources/views/Bundles/Warehouse/Livewire
```

## Local Setup

This machine did not have PHP or Composer on PATH while the project was created, so install/enable them first.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Login:

```text
admin@example.com
password
```

## Verification Done Here

```bash
npm install
npm run build
```

The local app is configured for MySQL database `zadanie` on `127.0.0.1:3306` with user `root` and an empty password.
