# UI/UX V1 Implementation Status

> Status harus mencerminkan bukti, bukan rencana. Branch tracking: `docs/ui-v1-scope-and-pr-workflow` · [PR #27](https://github.com/leonsmith128/pt-sanmal/pull/27).

## Vocabulary

- **Not started** — belum ada implementasi tercatat.
- **In progress** — pekerjaan sedang berlangsung.
- **Implemented, unverified** — source tersedia, tetapi pemeriksaan yang diperlukan belum lengkap.
- **Verified** — test dan visual/device QA yang relevan memiliki bukti.
- **Blocked** — dependency/keputusan menghalangi pekerjaan.
- **Accepted limitation** — owner menerima gap yang terdokumentasi.

Source inspection saja tidak cukup untuk status Verified.

## V1 milestones

| Milestone | Status | Catatan / bukti |
|---|---|---|
| Button dan action hierarchy | **Verified** | Shared button dipakai pada alur Admin, Employee, dan authentication yang diaudit. CI lulus pada commit `00d255c1d5384eb312e7842e85954c8ef7c9ca15`, [run 1004](https://github.com/leonsmith128/pt-sanmal/actions/runs/41543440506). Owner mengonfirmasi visual QA primary button pada light/dark dan beberapa ukuran layar; detail device/browser tidak tercatat. [QA log](VISUAL-QA-LOG.md). |
| Form controls | Implemented, unverified | Primitive field/input/select/textarea/checkbox dan focused tests tersedia. Periksa source, penggunaan nyata, dan hasil test terbaru sebelum mengubah status. |
| Badge dan domain status | Implemented, unverified | Primitive badge dan adapter domain tersedia; verifikasi mapping serta state pada halaman nyata. |
| Card, layout, typography | In progress | Primitive page/layout/card dan standardisasi Admin tersedia; review konsistensi Employee dan typography aktual. |
| Feedback states | Implemented, unverified | Flash/empty-state tersedia; verifikasi loading/error/confirmation pada workflow nyata. |
| Tables, lists, filters, pagination | In progress | Pola page-specific tersedia; verifikasi responsivitas dan pertahankan kontrak data tiap halaman. |
| Employee mobile-first workflows | In progress | Selesaikan review halaman dan device QA sesuai workflow kritis. |
| Admin desktop/tablet workflows | Implemented, unverified | Standardisasi source Admin masuk melalui PR #25; audit terdahulu bukan bukti visual QA browser lintas viewport. |
| Navigation dan application shell | In progress | Verifikasi state aktual Admin/Employee dan responsive behavior. |
| Accessibility | In progress | Perlu pemeriksaan sistematis page-level, keyboard, focus, semantics, dan touch. |
| Responsive/visual regression QA | Not started | Catat hasil per halaman, viewport, theme, dan critical state di QA log. |
| Final V1 review | Not started | Rekonsiliasi scope, status, test, QA, limitation, dan PR review sebelum merge. |

Status komponen yang sudah ada tidak otomatis berarti adopsi seluruh halaman atau QA sudah selesai. Periksa source, tests, dan browser evidence sebelum mengubah status.

## Format catatan milestone

Tambahkan entri ringkas saat milestone berubah:

- **Tanggal / commit:**
- **Scope dan source:**
- **Kontrak yang dipertahankan:**
- **Automated checks yang benar-benar dijalankan:**
- **Browser/device checks yang benar-benar dijalankan:**
- **Hasil dan limitation:**
- **Status:**

Detail viewport dan observasi visual hanya ditulis di [Visual QA Log](VISUAL-QA-LOG.md), jangan diduplikasi di sini.

## Button — Verified (2026-11-03)

- **Branch/PR:** `docs/ui-v1-scope-and-pr-workflow`, [PR #27](https://github.com/leonsmith128/pt-sanmal/pull/27).
- **Verified commit:** `00d255c1d5384eb312e7842e85954c8ef7c9ca15`.
- **Scope:** shared navigation/action controls pada authentication, Employee, dan alur Admin yang diaudit; native controls dipertahankan untuk calendar-day selection dan photo-preview trigger.
- **Behavior/contracts:** route, form method/action, CSRF, method spoofing, Livewire action/loading target, Alpine callback, confirmation, disabled state, accessible name, dan data-test hooks dipertahankan.
- **Automated check:** CI `ci` lulus pada commit tersebut, [run 1004](https://github.com/leonsmith128/pt-sanmal/actions/runs/41543440506).
- **Visual QA:** owner mengonfirmasi primary button diperiksa pada light/dark dan beberapa ukuran layar serta tampilannya lulus. Browser/version, device, ukuran viewport, screenshot, dan state interaksi individual tidak tersedia pada catatan konfirmasi; lihat [QA log](VISUAL-QA-LOG.md).
- **Limitation:** specialized native controls tetap di luar wrapper generik dengan alasan semantik/interaksi.

## Form controls — Batch 1 migration (in progress, 2026-11-03)

- **Branch/PR:** `docs/ui-v1-scope-and-pr-workflow`, [PR #27](https://github.com/leonsmith128/pt-sanmal/pull/27).
- **Scope:** Employee request-center filters and attendance-history date filters.
- **Source:** `resources/views/pages/employee/leave-requests/index.blade.php` and `resources/views/pages/employee/attendance-history/index.blade.php`.
- **Contracts:** GET field names (`q`, `type`, `status`, `from`, `to`), filter values, option values, route actions, reset links, export query forwarding, and date-filter validation rendering remain unchanged.
- **Tests added/extended:** `RequestCenterTest` covers filter controls and retained query values; `AttendancePagesTest` covers retained date values and reset/apply actions.
- **Automated checks:** CI for the current branch head is running; result not yet available.
- **Browser/device checks:** Not run. No visual QA pass is claimed.
- **Component changes:** None. Existing `x-ui.input` and `x-ui.select` primitives were sufficient.
- **Status:** In progress pending CI completion and browser/viewport review.

## Dokumentasi dan status handoff

Dokumentasi UI aktif terdiri dari Development Handoff, Design Foundation, Page Inventory, Component Catalog, Reusable Blade Components, file status ini, dan Visual QA Log. Audit UI lama yang sudah diserap atau tidak lagi relevan dihapus; jangan membuat ulang audit paralel. Tambahkan keputusan atau temuan baru ke dokumen aktif yang tepat.


## Shared component design foundation — batch implementation (2026-11-03)

- **Branch/PR:** `docs/ui-v1-scope-and-pr-workflow`, [PR #27](https://github.com/leonsmith128/pt-sanmal/pull/27).
- **Scope:** shared design tokens and all 14 existing `x-ui.*` primitives: badge, button, card, checkbox, empty-state, field, flash-message, input, metadata-item, page-header, page, section, select, and textarea.
- **Design tokens:** centralized SanMal brand blue/dark blue/light blue/bright blue/yellow, focus, and semantic feedback colors in Tailwind v4 theme tokens. Existing Instrument Sans and Flux accent defaults remain unchanged to avoid unintended global restyling.
- **Interaction and layout foundation:** standardized primary/outline button state styling, focus visibility, disabled affordance, Flux field label/description/error typography, control width, min-width guards, linked-card focus treatment, dark-surface contrast, page/header/section composition, and metadata wrapping.
- **Compatibility:** retained existing component names and public props. Preserved Flux rendering, form names/IDs, option/slot content, Livewire/Alpine/native attribute forwarding, validation display, link/button semantics, and existing default page/card spacing.
- **Targeted correction:** table-mode `x-ui.empty-state` now forwards caller attributes to its `td`; a focused regression test was added.
- **Automated checks:** initial CI run [1030](https://github.com/leonsmith128/pt-sanmal/actions/runs/41549222448) passed PHP formatting and both static-analysis stages, then exposed two markup-contract regressions: the page container's extra class broke an existing exact markup assertion, and table empty-state attribute formatting broke an existing selector assertion. Both source issues were corrected in subsequent commits; rerun CI on the corrected branch head before claiming automated verification. No local test execution is available from this GitHub editing session.
- **Browser/device checks:** Not run for this batch. Light/dark and viewport visual QA remains outstanding.
- **Status:** **Implemented, unverified**. Do not begin the all-page adoption pass until CI and component-level visual QA have been reviewed.
