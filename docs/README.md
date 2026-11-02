# Dokumentasi PT SanMal

Pintu masuk dokumentasi repository. Mulai dari ringkasan, lalu buka hanya panduan yang relevan dengan pekerjaan.

## Mulai di sini

1. [Repository README](../README.md) — tujuan aplikasi dan stack.
2. [Project Status](PROJECT-STATUS.md) — status repository, risiko, dan kesiapan production.
3. [Architecture & Development](ARCHITECTURE-AND-DEVELOPMENT.md) — struktur Laravel, Blade/Livewire, domain, testing.
4. [AI Agent Rules](../AGENTS.md) — aturan wajib sebelum mengubah kode.
5. [UI/UX Development Handoff](ui/DEVELOPMENT-HANDOFF.md) — workflow UI/UX V1 dan batas perubahan.

## UI/UX V1 — dokumen aktif

| Dokumen | Sumber kebenaran untuk |
|---|---|
| [Development Handoff](ui/DEVELOPMENT-HANDOFF.md) | Cara memulai, workflow, protected contracts, acceptance criteria, merge readiness |
| [Design Foundation](ui/DESIGN-FOUNDATION.md) | Brand, seamless-first, responsive priorities, visual standards |
| [Page Inventory](ui/PAGE-INVENTORY.md) | Cakupan halaman Employee/Admin/shared dan fokus review |
| [Component Catalog](ui/COMPONENT-CATALOG.md) | Peta primitive, aturan adopsi, standar dokumentasi komponen |
| [Reusable Blade Components](ui/REUSABLE-BLADE-COMPONENTS.md) | Detail API/kontrak komponen |
| [Implementation Status](ui/progress/IMPLEMENTATION-STATUS.md) | Status milestone dan bukti penyelesaian |
| [Visual QA Log](ui/progress/VISUAL-QA-LOG.md) | Hasil browser/device QA aktual |

## Dokumentasi teknis lain

| Dokumen | Kegunaan |
|---|---|
| [View Architecture](view-architecture.md) | Catatan arsitektur view; baca sebagai referensi historis dan cocokkan dengan arsitektur terkini |
| [Attendance Phase 2 Spec](attendance-phase-2-spec.md) | Spesifikasi domain absensi |
| [Attendance Phase 2 Audit](attendance-phase-2-audit.md) | Audit implementasi domain absensi |
| [Attendance Google Drive Backup](attendance-google-drive-backup.md) | Catatan backup Google Drive |

## Aturan source of truth

1. Source code pada branch target adalah acuan implementasi aktual.
2. Route definitions dan automated tests adalah acuan kontrak yang diuji.
3. Keputusan produk/domain yang disetujui tetap berlaku sampai secara eksplisit diganti.
4. Design Foundation adalah acuan arah visual yang disetujui.
5. Implementation Status dan Visual QA Log hanya memuat pekerjaan/pemeriksaan dengan bukti.
6. Jika dokumen berbeda dari source/test, investigasi lalu perbarui dokumen aktif yang paling relevan. Jangan menebak atau mempertahankan duplikasi.
