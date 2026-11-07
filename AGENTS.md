# Instruksi untuk AI Coding Agent

Dokumen ini adalah aturan kerja repository. Baca juga status proyek dan arsitektur serta panduan development sebelum mengubah kode.

## 1. Cara memulai setiap tugas

1. Pastikan repository dan branch yang sedang dikerjakan.
2. Periksa status working tree dan commit terbaru.
3. Baca dokumentasi aktif yang relevan dengan area tugas. Untuk perubahan UI, wajib baca `docs/ui/DESIGN-FOUNDATION.md`, `docs/ui/PAGE-INVENTORY.md`, dan katalog/kontrak komponen terkait; lalu verifikasi route, source, serta test aktual.
4. Baca file implementasi aktual, route, model, migration, test, dan komponen terkait sebelum mengusulkan perubahan.
5. Bedakan fakta dari source, keputusan yang telah disepakati, rekomendasi, dan asumsi.
6. Buat perubahan sekecil mungkin dan tetap dalam ruang lingkup permintaan.

Jangan mengandalkan percakapan lama sebagai bukti bahwa kode saat ini masih sama. Source pada branch target adalah acuan utama.

## 2. Batas perubahan

- Jika tugas hanya meminta UI/UX atau standardisasi tampilan, jangan mengubah query, state, filter, sorting, pagination, route, otorisasi, validasi, persistence, perhitungan absensi, atau business logic.
- Ikuti `docs/ui/DESIGN-FOUNDATION.md`: Employee mobile-first; Admin mengutamakan keterbacaan informasi pada desktop/tablet dan tetap mobile-friendly. Terapkan desain langsung di source code, gunakan komponen yang ada, dan verifikasi browser pada viewport yang relevan.
- Jangan mengubah kontrak Livewire: nama property, action, event, lifecycle hook, binding, dan parameter tanpa kebutuhan yang eksplisit.
- Jangan mengubah alur kamera, GPS, watermark, upload, Clock In, Clock Out, atau lembur ketika tugas tidak mencakupnya.
- Jangan menghapus data historis atau melemahkan audit trail.
- Jangan menambah abstraksi/komponen generik sebelum ditemukan pengulangan markup sekaligus kontrak perilaku yang benar-benar sama.
- Jangan melakukan refactor besar hanya demi menyamakan gaya.

## 3. Aturan arsitektur

- Gunakan Blade + Controller untuk halaman HTTP yang sederhana dan tidak membutuhkan state reaktif kompleks.
- Gunakan Livewire 4 ketika interaksi server reaktif memang diperlukan.
- Native Livewire 4 Single-File Component (SFC) adalah pola yang valid. Jangan menyebut semua SFC sebagai Volt.
- Halaman resources/views/pages/admin/⚡kehadiran.blade.php adalah Native Livewire 4 SFC, bukan Volt.
- Jangan memigrasikan halaman ke Volt, class-based Livewire, atau framework frontend lain hanya demi migrasi.
- Komponen UI bertanggung jawab atas presentasi dan atribut; keputusan bisnis tetap berada di lapisan aplikasi/domain.
- Pertahankan komponen yang sudah ada sebelum membuat komponen baru. Lihat daftar primitive di dokumen arsitektur.

## 4. Aturan domain yang tidak boleh diasumsikan ulang

- users dan employees adalah entitas berbeda.
- superadmin tidak wajib memiliki profil Employee.
- admin dan employee harus memiliki profil Employee.
- Location office menggunakan client_id = null.
- Location site harus dimiliki Client.
- Satu Project memiliki satu Location.
- Project yang tersedia untuk absensi harus memenuhi aturan status dan Location pada implementasi domain terkini.
- AttendanceRecord menyimpan konteks Location/Project yang relevan pada saat absensi; jangan mengganti konteks historis dengan relasi master yang telah berubah.
- Status persetujuan lembur dan state operasional lembur adalah dua konsep berbeda.
- Timestamp bukti asli dan timestamp efektif hasil koreksi harus tetap dapat dibedakan.

Jika source bertentangan dengan dokumen, jangan langsung memilih salah satu. Laporkan perbedaan dan periksa keputusan/domain test terbaru sebelum mengubahnya.

## 5. PHP, format, dan keamanan

- Ikuti style yang diterapkan Laravel Pint.
- Gunakan namespace PHP dengan satu leading backslash bila fully-qualified name diperlukan, contoh: \App\Enums\LeaveRequestStatus::Pending.
- Jangan menulis namespace dengan double leading slash.
- Jangan menyimpan .env, APP_KEY, password, token, kredensial hosting, atau data pribadi ke repository.
- Jangan menonaktifkan middleware, policy, validasi, CSRF, atau proteksi upload untuk membuat test lolos.
- Jangan menjalankan migrate:fresh, db:wipe, truncate, atau operasi destruktif pada database yang mungkin berisi data.
- Jangan mengarang hasil test, lint, CI, browser test, atau verifikasi perangkat.

## 6. Pengujian dan pelaporan

- Pilih test yang sesuai dengan perubahan, kemudian jalankan test suite yang relevan jika runtime tersedia.
- Untuk perubahan PHP, jalankan Pint atau pemeriksaan Pint sesuai workflow.
- Periksa MariaDB compatibility untuk migration dan constraint; SQLite pass saja tidak membuktikan kompatibilitas MariaDB.
- Jika test tidak dijalankan, nyatakan alasannya secara eksplisit.
- Bedakan hasil repository-wide PHPStan dari targeted PHPStan. Workflow saat ini menoleransi baseline repository-wide dengan continue-on-error.
- Laporkan file yang berubah, kontrak yang dipertahankan, test yang benar-benar dijalankan, hasil CI, dan keterbatasan verifikasi.

## 7. Git workflow

- Untuk finalisasi UI/UX sebelum produksi, gunakan satu branch `feature/ui-production-readiness` dan satu PR ke `main`. PR #27 dan #29 sudah di-merge dan tidak lagi menjadi acuan. Jangan membuat branch atau PR UI lain selama PR ini terbuka. Commit per fase, kecil dan fokus. Jangan merge sebelum Definition of Done di `docs/ui/DEVELOPMENT-HANDOFF.md` terpenuhi dan bukti QA tercatat di `docs/ui/progress/VISUAL-QA-LOG.md`.
- Commit focused, logically grouped changes to that same branch. Before every write, inspect the current branch HEAD and latest file SHA to avoid overwriting newer work.
- The authoritative workflow and handoff are documented in `docs/ui/DEVELOPMENT-HANDOFF.md`; design decisions are in `docs/ui/DESIGN-FOUNDATION.md`; scope is tracked in `docs/ui/progress/IMPLEMENTATION-STATUS.md`.

- Untuk pekerjaan di luar program UI/UX SanMal V1, secara default kerjakan di feature branch dari main, buat commit terfokus, lalu buka Pull Request. Untuk finalisasi UI/UX sebelum produksi, aturan satu branch dan satu PR di atas selalu berlaku.
- Commit langsung ke main hanya jika pengguna secara eksplisit memintanya.
- Jangan force-push, mengubah riwayat, merge, atau mengubah status PR tanpa instruksi eksplisit.
- Sebelum menulis ke branch, periksa ulang HEAD dan hindari menimpa perubahan yang masuk setelah inspeksi.
- Jangan menyatakan pekerjaan selesai sebelum perubahan berhasil di-fetch kembali dan diverifikasi.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
