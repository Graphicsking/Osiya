# Arthur Amug Osiya — Busia 2027

A Vue 3 campaign website with a Laravel 13 backend and a protected campaign dashboard. The visual identity uses forest green, warm ivory and muted gold.

## Project structure

`Osiya` is the single project root. Run PHP, Composer and npm commands here.

```text
Osiya/
├── app/                   # Controllers, models, admin command and middleware
├── routes/web.php         # Website, form endpoint and protected admin routes
├── resources/views/       # Website shell and admin dashboard templates
├── src/                   # Vue website components and styles
├── public/                # Web server document root
│   ├── images/            # Website photographs
│   ├── admin.css          # Admin dashboard styles
│   └── build/             # Generated Vue production assets
├── database/              # Migrations and local SQLite database
├── assets/photo-archive/  # Original photographs and unused copies
├── tests/                 # Laravel feature tests and Vue interaction tests
├── composer.json
├── package.json
└── vite.config.js
```

`vendor/` and `node_modules/` contain installed dependencies. The local `.env`, SQLite database and generated build files are ignored by Git.

## Run locally

Requires PHP 8.3 or newer with SQLite support, Composer, and Node.js/npm.

For a fresh checkout:

```sh
composer run setup
php artisan admin:create
php artisan serve --host=127.0.0.1 --port=8000
```

The setup command installs dependencies, creates `.env` and the local SQLite database, generates the app key, runs migrations and builds Vue. Run setup once for a fresh checkout; keep an existing app key when maintaining an installed application. `admin:create` prompts for an administrator name, email and password (at least 12 characters). There is no public account registration or default seeded password.

For this workspace, dependencies, the app key, SQLite migrations and frontend assets are already prepared. A local administrator was created and its sign-in details were provided in chat.

- Website: http://127.0.0.1:8000
- Admin sign in: http://127.0.0.1:8000/admin/login
- Admin dashboard: http://127.0.0.1:8000/admin

For frontend editing, keep Laravel running and start `npm run dev` in another terminal. Use the Laravel URL above; Laravel's Vite integration loads the Vue development assets. `npm run build` generates production assets in `public/build/`. Stop the Vite server when checking the production build.

## Dashboard

The dashboard shows total submissions, new submissions awaiting contact, submissions received today, and volunteers. Four clickable involvement cards group Volunteers, Share an idea, Community conversations and Campaign updates, showing total submissions and how many await contact. Group totals always include all records, independent of searches and pagination. Clicking a group opens its people as individual cards with name, phone number, involvement, submission date/time in Nairobi, and status. Cards are the default; an optional table view preserves the current filters. Search by name or phone number, filter by involvement or status, browse pages of 20 records, and mark a submission contacted or new.

Only authenticated administrators can view or update submissions. Passwords are hashed; session IDs rotate at sign-in. CSRF protection, server-side validation and request limits protect the forms. A contact-consent timestamp is recorded with each submission. Admin and form API responses are not cached.

## Verification

```sh
npm run build
npm test
php artisan test
```

The eight Vue production-bundle tests cover navigation, galleries, keyboard controls and submitting the form, including failed-request retries and success only after a confirmed save. Fifteen Laravel feature tests cover persistence, validation, consent, retry deduplication, request limits, admin authorization, login/logout, search, filters, status updates, pagination, HTML escaping, administrator creation, group totals, group navigation, card/table switching and filtered pagination. Laravel tests use an isolated in-memory database.

A real localhost HTTP check also verified CSRF enforcement, website/build assets, submission → SQLite → authenticated dashboard, status updates, filtering and logout. Its synthetic submission was removed. The local administrator remains available. Browser-based visual verification was unavailable in this session.

## Content and images

The candidate name was supplied by the user. The six supplied photographs served by the website are in `public/images`. All supplied originals have been preserved in `assets/photo-archive/originals`, and archived website copies are in `assets/photo-archive/not-used`. See `assets/photo-archive/README.md` for the source-to-website mapping and native dimensions. The largest source (960 × 640) leads the hero. Full-frame images and compact portrait sizing reduce enlargement and cropping; no artificial upscaling is used. Higher-resolution originals would further improve sharpness on high-density displays. The proposed priorities and campaign copy are drafts for approval, not assertions of a published manifesto, endorsements, accomplishments or scheduled events. No party is indicated.

The Get involved form sends name, phone number, involvement choice and contact consent to Laravel. The success message appears only after the database confirms the save. New submissions appear in the protected `/admin` dashboard. The form no longer saves drafts in localStorage. A submission UUID prevents a retry from creating a duplicate record.

Edit page content in `src/App.vue`, the interest form in `src/components/InterestDialog.vue`, and the design in `src/style.css`, `src/enhancements.css`, and `src/hero.css`. Paragraphs use 16px on desktop and 14px on mobile. The full-width hero takes layout cues from teyezillaexpeditions.com: dark background media, a white headline, a gold italic second line, and modern calls to action with 12px corners. The hero uses one static photograph covering the entire section with `object-fit: cover`, responsive focal positioning and a dark overlay. A CSS horizontal mirror places the candidate on the right while retaining the original image file. It has no carousel, playback controls or timers. The largest supplied photograph (960 × 640) is used; a higher-resolution original would improve full-screen sharpness. Gallery photos fill their frames and rows across the container, with six shown in All moments, three in Community, and three in Leadership. They can be filtered and opened in a keyboard-accessible lightbox with thumbnails for the selected filter. Fonts load from Google Fonts with system fallbacks. The layout respects reduced-motion preferences.

The frontend is deployed to GitHub Pages by `.github/workflows/pages.yml` when `main` changes. `npm run build:pages` builds the static site for `/Osiya/` and copies only the public website images, favicon and robots file. The Pages deployment serves the public website; the Get involved form and admin dashboard require Laravel on a PHP-capable host. The `.openai/hosting.json` file records the earlier static Sites deployment.

To publish the full Laravel application, deploy to a PHP-capable host, point its web root at `public/`, configure its database and HTTPS, set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` to the public URL and `SESSION_SECURE_COOKIE=true`, then run migrations and the frontend build. Do not publish the project root or `.env`. Keep the application key and database backed up.

Global horizontal alignment is defined in `src/layout.css`. All section containers share `--page-gutter` (20–56px, responsive) and a 1280px content maximum. The candidate image and shared-belief messages align with this same content boundary rather than adding separate centering offsets.

The closing section uses native CSS animations in `src/join-motion.css`, without an animation library. The year and its caption float gently over eight seconds. The two inner rings rotate anticlockwise over 90 and 120 seconds; the outer ring rotates clockwise over 150 seconds. Faint markers make the rotation visible, and all of this motion stops when reduced motion is preferred.
