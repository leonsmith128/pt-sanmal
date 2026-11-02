# Dokumentasi PT SanMal

Dokumen ini adalah pintu masuk dokumentasi repository. Pilih panduan berdasarkan pekerjaan; jangan membaca seluruh arsip audit jika hanya mengubah satu halaman.

## Mulai untuk kontributor baru

1. [Repository README](../README.md) — tujuan aplikasi dan stack.
2. [Project Status](PROJECT-STATUS.md) — milestone repository, risiko, dan status project.
3. [Architecture & Development](ARCHITECTURE-AND-DEVELOPMENT.md) — struktur Laravel, Blade/Livewire, domain, testing.
4. [AI Agent Rules](../AGENTS.md) — instruksi wajib untuk coding agent.
5. [UI/UX Development Handoff](ui/DEVELOPMENT-HANDOFF.md) — alur kerja UI/UX V1, batas scope, dan Definition of Done.

## UI/UX V1 — authoritative references

| Dokumen | Sumber kebenaran untuk |
|---|---|
| [Development Handoff](ui/DEVELOPMENT-HANDOFF.md) | Cara memulai, workflow, batas perubahan, acceptance criteria, dan merge readiness |
| [Design Foundation](ui/DESIGN-FOUNDATION.md) | Brand, seamless-first, responsive role priorities, dan visual standards |
| [Page Inventory](ui/PAGE-INVENTORY.md) | Cakupan halaman Employee/Admin/shared dan tautan audit |
| [Component Catalog](ui/COMPONENT-CATALOG.md) | Peta primitive, aturan adopsi, dan standar dokumentasi komponen |
| [Reusable Blade Components](ui/REUSABLE-BLADE-COMPONENTS.md) | Detail API/kontrak komponen yang sudah didokumentasikan |
| [Implementation Status](ui/progress/IMPLEMENTATION-STATUS.md) | Status milestone dan bukti penyelesaian |
| [Visual QA Log](ui/progress/VISUAL-QA-LOG.md) | Hasil browser/device QA aktual |

## UI audit and historical records

| Dokumen | Kegunaan |
|---|---|
| [Admin Pages Audit](ui/ADMIN-PAGES-AUDIT.md) | Audit dan catatan implementasi standardisasi halaman Admin |
| [P1-D3 Expanded Page Audit](ui/P1-D3-expanded-page-audit.md) | Inventaris dan rekomendasi awal lintas halaman |
| [P1-A-B Audit](ui/P1-A-B-audit.md) | Audit terdahulu |
| [P1-D Audit](ui/P1-D-audit.md) | Audit tabel/list Admin |
| [P1-D1 Spacing & Density](ui/P1-D1-spacing-density.md) | Baseline spacing, density, dan hierarki tombol |
| [P1-D2 Project Expandable Card](ui/P1-D2-project-expandable-card.md) | Catatan pola expandable card Master Proyek |

Audit adalah catatan berdasarkan baseline saat audit dilakukan. Gunakan source branch terkini untuk memastikan kondisi aktual. Jangan mengubah temuan historis menjadi klaim QA terbaru.

## Other technical documentation

| Dokumen | Kegunaan |
|---|---|
| [View Architecture](view-architecture.md) | Sejarah/pedoman arsitektur view; cek konsistensi dengan panduan arsitektur terkini |
| [Attendance Phase 2 Spec](attendance-phase-2-spec.md) | Spesifikasi historis domain absensi |
| [Attendance Phase 2 Audit](attendance-phase-2-audit.md) | Audit implementasi domain absensi |
| [Attendance Google Drive Backup](attendance-google-drive-backup.md) | Catatan backup Google Drive |

## Source-of-truth rules

When documents or historical notes differ:

1. Current source code on the target branch is authoritative for implementation behavior.
2. Current automated tests and route definitions are authoritative evidence of tested contracts.
3. Explicitly approved product/domain decisions remain authoritative unless superseded.
4. Design Foundation is authoritative for approved visual direction.
5. Page audits describe the baseline they inspected; they do not automatically describe current source or completed browser QA.
6. Implementation Status and Visual QA Log must describe only work and checks supported by actual evidence.

If a conflict is discovered, record it, inspect the relevant source/tests, and update the narrowest authoritative document. Do not silently guess.
