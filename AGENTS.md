# Instructions For AI Agents

This file is intended for AI coding agents working on RaccoOn. It should be readable as plain Markdown or as a raw repository file, without depending on vendor-specific configuration.

Suggested raw URL: `https://raw.githubusercontent.com/justfernandomalpica/mpk-project/main/AGENTS.md`.

## Goal

Help users start new projects from this scaffold and generate common pieces without manually writing every class, route, view, or model. Use this file as an operating guide, but always validate the current project version by reading the internal documentation first.

## Before Generating Code

1. Read `README.md` to understand requirements, commands, structure, and the request flow.
2. Read `composer.json` to confirm the PSR-4 namespace, minimum PHP version, and dependencies.
3. Read `package.json` to confirm frontend scripts and Vite dependencies.
4. Read the PHPDoc for the classes you are going to use. Prioritize:
   - `src\Routing\Router`
   - `src\Routing\Route`
   - `src\Http\Response`
   - `src\Rendering\Render`
   - `src\Rendering\View`
   - `src\Rendering\Partial`
   - `src\Database\ActiveRecord`
   - `src\Support\Vite`
5. If `README.md` and PHPDoc disagree, treat the source code and nearby PHPDoc as the best description of the current version. Report the mismatch before assuming behavior.

## Quick Start For A New Project

Use these steps when a user asks to start a new project from RaccoOn:

```bash
git clone https://github.com/justfernandomalpica/mpk-project.git my-project
cd my-project
php scripts/init-project.php my-project
composer install
composer dump-autoload
npm install
```

Important to ask the user the name of the project before running any script.

The initializer updates `composer.json`, `package.json`, and `package-lock.json`, and creates `.env` from `.env.example` only when `.env` is missing. If project metadata was changed manually, run:

```bash
composer update --lock
npm install --package-lock-only
```

If the project needs local environment variables:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

For local development:

```bash
npm run dev
```

This starts PHP at `http://localhost:8000` and Vite at `http://localhost:5173`. The main app opens at `http://localhost:8000`; Vite's root URL can return 404 because it only serves frontend assets.

If `npm run dev` fails or the ports are not active, run the processes separately to expose the failing side:

```bash
npm run dev:vite
npm run dev:php
```

On restricted Windows sandboxes, Vite/esbuild may need normal filesystem read access to load `vite.config.ts`. Prefer diagnosing with `npm run dev:vite` before changing PHP routing.

## Common Piece Maker

When the user asks for a new feature, create the required pieces by following the existing patterns. Do not ask the user to create classes manually when you can infer a reasonable structure from the scaffold.

### Simple HTML Page

For a new page:

1. Create or update a controller in `src/Controllers`.
2. Add the route in `routes/web.php`.
3. Create the view in `views/pages`.
4. Use `Response::html(Render::view(...))` when the page should use a layout.
5. Use `View` to pass data to the template. In the view, data is available with the `$data_` prefix.

Expected example:

```php
$router->get('/about', [PageController::class, 'about']);
```

```php
Response::html(Render::view(
    layout: 'main',
    view: (new View('about'))->data([
        'title' => 'About',
    ])
));
```

### JSON Endpoint

For an API route:

1. Add the route in `routes/api.php` or the appropriate routes file.
2. Use a controller in `src/Controllers`.
3. Respond with `Response::json($payload, JSON_SUCCESS)` or the response format defined by the current constants.

Expected example:

```php
$router->get('/api/status', [StatusController::class, 'index']);
```

```php
Response::json([
    'status' => 'ok',
], JSON_SUCCESS);
```

### ActiveRecord Model

For a database-backed model:

0. Ask the user for the components of the SQL table, such as name and columns, also ask for column datatype. ActiveRecord models in this project are modeled to be a equivalent of real SQL tables. 
1. Confirm `.env` has `APP_USE_DATABASE=true`.
2. Create the class in `src/Models`.
3. Extend `App\Database\ActiveRecord`.
4. Define `$table`, `$columns`, and `$columnsToSync`.
5. Declare properties that match the columns in use.
6. Use `sync()` to load external data before `save()` or `update()`.


Do not invent migrations if the project does not have them. If a SQL table is needed, generate the SQL as a separate proposal.

### Reusable Partial

For a view component:

1. Create the file in `views/partials`.
2. Render it with `Render::partial(new Partial('name'))`.
3. Pass data with `Partial::data([...])`.
4. In the partial, use variables with the `$data_` prefix.

## Scaffold Conventions

- Use `<?php declare(strict_types=1);` in new PHP classes.
- Keep the `App\...` namespace aligned with the folder under `src/`.
- Use `routes/web.php` for HTML pages and `routes/api.php` for JSON endpoints when applicable.
- Use `Response` to emit HTTP responses.
- Use `Render`, `View`, and `Partial` for HTML templates.
- Use `s()` to escape HTML output in views.
- Do not edit `vendor/`, `public/build/`, `node_modules/`, or generated files.
- Avoid introducing full frameworks unless the user explicitly asks for one. RaccoOn favors clarity and low overhead.
- Keep changes small and aligned with the existing structure.

## Agent Checklist

Before finishing a task:

1. Confirm namespaces and file paths match PSR-4.
2. Confirm new routes are registered.
3. Confirm referenced views exist.
4. If you added frontend code, confirm the Vite entrypoints exist.
5. Run a reasonable verification depending on the change:
   - `composer dump-autoload` if you added PHP classes.
   - `npm run typecheck` if you touched TypeScript.
   - `npm run build` if you changed frontend assets.
6. Summarize modified files and any remaining user-facing steps.


## Useful User Prompts

Users can ask for tasks like:

- "Create a contact page with route, controller, and view."
- "Create a JSON endpoint to list products."
- "Create an ActiveRecord model for the users table."
- "Add a reusable partial for alerts."
- "Read the README and PHPDoc before changing this feature."

The agent should convert these requests into concrete scaffold changes after reading the current project documentation.
