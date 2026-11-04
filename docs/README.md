# Dokumentasi PT SanMal

Pintu masuk dokumentasi repository. Mulai dari ringkasan, lalu buka hanya panduan yang relevan dengan pekerjaan.

## Mulai di sini

1. [Repository README](../README.md) — tujuan aplikasi dan stack.
2. [Project Status](PROJECT-STATUS.md) — status repository, risiko, dan kesiapan production.
3. [AI Agent Rules](../AGENTS.md) — aturan kerja wajib.
4. [Architecture & Development](ARCHITECTURE-AND-DEVELOPMENT.md) — struktur Laravel/Livewire, route, domain, dan testing.
5. [UI/UX Development Handoff](ui/DEVELOPMENT-HANDOFF.md) — cara melanjutkan UI/UX V1 tanpa membaca semua dokumen sekaligus.

## UI/UX V1 — dokumen aktif

| Dokumen | Sumber kebenaran untuk |
|---|---|
| [Development Handoff](ui/DEVELOPMENT-HANDOFF.md) | Urutan membaca, arsitektur UI, workflow, protected contracts, dan merge gate |
| [Design Foundation](ui/DESIGN-FOUNDATION.md) | Brand, token aktual, seamless-first, shell, card, responsive priorities |
| [Page Inventory](ui/PAGE-INVENTORY.md) | Cakupan halaman Employee/Admin/shared dan fokus review |
| [Component Catalog](ui/COMPONENT-CATALOG.md) | Peta primitive dan domain component, aturan adopsi |
| [Reusable Blade Components](ui/REUSABLE-BLADE-COMPONENTS.md) | Detail API/kontrak primitive bersama |
| [Implementation Status](ui/progress/IMPLEMENTATION-STATUS.md) | Kondisi implementasi, CI terbaru, gap, dan pekerjaan berikutnya |
| [Visual QA Log](ui/progress/VISUAL-QA-LOG.md) | Bukti browser/device QA aktual; bukan daftar rencana |
| [Employee Dashboard Layout V1](ui/EMPLOYEE-DASHBOARD-LAYOUT-V1.md) | Komposisi UI dashboard pegawai |
| [Employee Dashboard Data Contract](ui/EMPLOYEE-DASHBOARD-DATA-CONTRACT.md) | Definisi status, timezone, durasi kerja, lembur, dan agregasi |
| [Employee Attendance Layout V1](ui/EMPLOYEE-ATTENDANCE-LAYOUT-V1.md) | Urutan halaman absensi, ringkasan, status, form, kamera, dan preview |
| [Employee Attendance Calendar V1](ui/EMPLOYEE-ATTENDANCE-CALENDAR-V1.md) | Weekly calendar, monthly picker, indikator, dan perilaku tanggal |

## Pilih dokumen berdasarkan pekerjaan

| Jika pekerjaanmu... | Mulai dari |
|---|---|
| Mengubah tampilan halaman | Development Handoff → Design Foundation → Page Inventory → spesifikasi halaman terkait |
| Mengubah atau membuat komponen bersama | Component Catalog → Reusable Blade Components → source dan focused tests |
| Mengubah Employee Dashboard | Employee Dashboard Layout V1 + Employee Dashboard Data Contract |
| Mengubah halaman Absensi Pegawai | Employee Attendance Layout V1 + Calendar V1 + source camera/preview yang relevan |
| Memeriksa apakah pekerjaan sudah selesai | Implementation Status → Visual QA Log → CI pada branch terbaru |
| Mengubah aturan bisnis atau struktur Laravel | Architecture & Development + domain spec terkait + AGENTS.md |

Tidak perlu membaca seluruh dokumen UI untuk setiap task. Baca dokumen yang sesuai dengan area perubahan, lalu verifikasi source, route, dan test aktual.

## Dokumentasi teknis lain

| Dokumen | Kegunaan |
|---|---|
| [View Architecture](view-architecture.md) | Catatan arsitektur view; referensi historis, cocokkan dengan arsitektur terkini |
| [Attendance Phase 2 Spec](attendance-phase-2-spec.md) | Spesifikasi domain absensi |
| [Attendance Phase 2 Audit](attendance-phase-2-audit.md) | Audit implementasi domain absensi |
| [Attendance Google Drive Backup](attendance-google-drive-backup.md) | Catatan backup Google Drive |

## Aturan source of truth

1. Source code pada branch target adalah acuan implementasi aktual.
2. Route definitions dan automated tests adalah acuan kontrak yang diuji.
3. Keputusan produk/domain yang disetujui tetap berlaku sampai secara eksplisit diganti.
4. Design Foundation adalah acuan arah visual; nilai token harus sesuai stylesheet aktual.
5. Implementation Status dan Visual QA Log hanya memuat pekerjaan/pemeriksaan dengan bukti.
6. Jika dokumentasi berbeda dari source/test, investigasi lalu perbarui dokumen aktif yang paling relevan. Jangan menebak atau mempertahankan duplikasi.
