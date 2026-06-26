# Brain4 Laravel Conversion

This repository now contains a Laravel-based version of the app in the repository root, built with Tailwind CSS and Vite.

## Project structure

- `legacy-app/` — full copy of the original project, preserved for reference
- root — Laravel app with Tailwind CSS front-end

## Laravel app setup

### Requirements

- PHP 8.1+
- Composer
- Node.js + npm

### Install

```bash
composer install
npm install
```

### Run locally

```bash
php artisan serve
npm run dev
```

Then visit `http://127.0.0.1:8000`.

## Notes

- The original React/Vite/Supabase project has been copied into `legacy-app/`.
- The new Laravel app uses `@vite` to compile `resources/css/app.css` and `resources/js/app.js`.
