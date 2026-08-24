# AI Document Journal

SaaS multi-workspace: unggah nota/invoice, deteksi duplikasi, ekstraksi AI, review, approval, dan export jurnal.

## Stack

Laravel 13, Inertia + Vue 3, PostgreSQL, Redis, Horizon, object storage privat.

## Menjalankan lokal

1. Salin `.env.example` ke `.env` lalu isi database, Redis, dan `OPENAI_API_KEY` (opsional; tanpa key memakai extractor stub).
2. `composer install && npm install`
3. `php artisan key:generate && php artisan migrate --seed`
4. `composer run dev` (atau `php artisan serve` + `npm run dev` + `php artisan horizon`)

Akun demo setelah seed:

- `owner@example.test` / `password`
- `uploader@example.test` / `password`
- `reviewer@example.test` / `password`
- `approver@example.test` / `password`
