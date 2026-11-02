# Indeks Dokumentasi PT SanMal

Gunakan halaman ini sebagai titik masuk dokumentasi. Dokumen dipisahkan berdasarkan fungsi agar developer dan AI agent dapat menemukan sumber yang tepat tanpa membaca seluruh catatan proyek.

## Dokumen inti

| Dokumen | Pembaca utama | Isi |
|---|---|---|
| [Project Status](PROJECT-STATUS.md) | Semua kontributor | Status branch/main, fase implementasi, CI, risiko, dan next steps |
| [Architecture & Development Guide](ARCHITECTURE-AND-DEVELOPMENT.md) | Developer dan AI agent | Struktur source, pemilihan Blade/Livewire, domain, UI primitives, test, dan workflow |
| [AI Agent Rules](../AGENTS.md) | AI coding agent | Batas perubahan dan aturan repository |
| [Repository README](../README.md) | Semua pembaca | Ringkasan aplikasi dan tautan dokumentasi |

## Audit UI dan standardisasi

| Dokumen | Status/kegunaan |
|---|---|
| [Audit Menyeluruh Halaman Admin](ui/ADMIN-PAGES-AUDIT.md) | Rekap implementasi standardisasi halaman Admin, konsistensi akhir, dan verifikasi |
| [P1-D Audit](ui/P1-D-audit.md) | Audit awal tabel/list Admin dan prinsip pemilihan pola presentasi |
| [P1-D3 Expanded Page Audit](ui/P1-D3-expanded-page-audit.md) | Inventarisasi halaman lintas role dan rekomendasi UI/UX; bukan bukti visual QA menyeluruh |
| [P1-D1 Spacing & Density](ui/P1-D1-spacing-density.md) | Baseline spacing, density, dan hierarki tombol |
| [P1-D2 Project Expandable Card](ui/P1-D2-project-expandable-card.md) | Spesifikasi pola expandable card Master Proyek |

## Cara membaca status dokumen audit

Dokumen audit dapat berisi temuan pada baseline lama dan catatan implementasi yang lebih baru. Gunakan aturan berikut:

1. Bagian baseline/temuan awal menjelaskan kondisi ketika audit dilakukan; jangan menganggapnya otomatis menggambarkan source main saat ini.
2. Bagian progress implementasi dan status akhir menunjukkan pekerjaan yang kemudian diterapkan.
3. Bagian batas verifikasi menentukan apa yang benar-benar diperiksa. Inspeksi source, automated test, CI, browser QA, dan pengujian perangkat adalah jenis bukti yang berbeda.
4. Jika ada perbedaan, periksa source pada main dan commit terkait sebelum membuat perubahan.

## Urutan membaca untuk kontributor baru

1. [README proyek](../README.md)
2. [Status proyek](PROJECT-STATUS.md)
3. [Arsitektur dan panduan development](ARCHITECTURE-AND-DEVELOPMENT.md)
4. [Instruksi AI agent](../AGENTS.md), jika menggunakan coding agent
5. Audit spesifik sesuai area yang akan dikerjakan
