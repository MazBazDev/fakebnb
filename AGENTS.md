# Repository Guidelines

## Project Structure & Module Organization
This monorepo hosts a Laravel API and a Vue 3 SPA.
- `apps/api/`: Laravel 12 REST API (routes, controllers, policies, tests in `apps/api/tests`).
- `apps/app/`: Vue 3 + Vite SPA (source in `apps/app/src`, tests near source).
- `docs/`: architecture and API docs (see `docs/architechture.md` for target design).
- `infra/`: local infra scripts and tooling.

## Build, Test, and Development Commands
Run commands from the relevant app folder.
- API setup: `composer run setup` (installs deps, prepares `.env`, migrates, builds assets).
- API dev: `composer run dev` (artisan server + queue + logs + Vite).
- API tests: `composer run test` or `php artisan test`.
- SPA dev: `npm run dev` (Vite dev server).
- SPA build: `npm run build` (type-check + production build).
- SPA tests: `npm run test:unit` (Vitest).
- SPA lint/format: `npm run lint`, `npm run format`.

## Coding Style & Naming Conventions
- Laravel (PHP): 4‑space indent; follow PSR‑12 and Laravel conventions. Use Laravel Pint if formatting is needed.
- Vue/TS: 2‑space indent, `printWidth: 100`, single quotes, no semicolons (`apps/app/.prettierrc.json`).
- Filenames: PHP classes `StudlyCase`, Vue components `PascalCase.vue`, composables `useXxx.ts`.

## Testing Guidelines
- API tests live in `apps/api/tests` and should be named `*Test.php`.
- SPA unit tests use Vitest; prefer `*.test.ts` or `*.spec.ts` next to `src/`.
- Aim to cover auth, listings, bookings anti‑conflict, messaging, and roles per `docs/architechture.md`.

## Commit & Pull Request Guidelines
- Git history currently contains only “unit repos”; no established convention yet.
- Use concise, action‑oriented commit messages (e.g., `Add listing policy checks`).
- PRs should include: summary, test results (commands run), and screenshots for UI changes.

## Architecture & Docs
- Follow the architecture and API standards in `docs/architechture.md`.
- Keep `docs/` updated when endpoints or roles/permissions change.

## Agent‑Specific Instructions
- Prefer minimal, targeted changes; keep docs concise and actionable.
- Do not add new tooling unless requested; align with the Laravel + Vue stack already initialized.
