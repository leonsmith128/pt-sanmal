# Status Proyek PT SanMal

> Dokumen ini adalah ringkasan status pada saat pembaruan. Untuk status branch, commit, dan CI yang berubah setelahnya, periksa GitHub Actions dan branch main secara langsung.

## 1. Ringkasan

- Repository: leonsmith128/pt-sanmal
- Branch utama: main
- Status: pengembangan aktif; belum dinyatakan siap production.
- Stack: Laravel 13, PHP 8.3+, Livewire 4, Blade, Tailwind CSS 4, Flux UI, MariaDB/MySQL.
- Fokus saat ini: verifikasi hasil merge, regression testing, security review, dan persiapan lingkungan deployment.
- Deployment target, domain, hosting, dan konfigurasi production belum dikonfirmasi dalam dokumentasi yang tersedia.

## 2. Milestone repository yang sudah masuk ke main

| Milestone | Referensi | Ringkasan |
|---|---|---|
| Konsolidasi arsitektur Views V1 | [Merge PR #20](https://github.com/leonsmith128/pt-sanmal/commit/4134b08c9f235044dc97f3634bd445c8f22e1871) | Pengorganisasian halaman ke struktur pages::... dan konsolidasi arsitektur Views |
| Workflow absensi kamera dan domain Client–Location–Project | [Merge PR #24](https://github.com/leonsmith128/pt-sanmal/commit/0298a8f3fa3725eafb8fd7112f3ffe41cff033fe) | Workflow kamera/GPS, sesi absensi, koreksi, lembur, aturan domain master, dan pengujian lintas database |
| Standardisasi UI Admin | [Merge PR #25](https://github.com/leonsmith128/pt-sanmal/commit/ea72d26379a5552df8e65ab9da738f98b5b6bdd5) | Standardisasi primitive UI, filter, form, badge, metadata, container/header, dan konsistensi akhir halaman Admin |

Commit merge PR #25 (baseline kode untuk catatan standardisasi UI Admin) adalah ea72d26379a5552df8e65ab9da738f98b5b6bdd5. Setelahnya, dokumentasi proyek diperbarui melalui beberapa commit docs-only langsung ke main.

## 3. Status pekerjaan per area

| Area | Status | Catatan |
|---|---|---|
| Arsitektur Views | Implementasi V1 sudah di-merge | Gunakan struktur halaman pages/ dan route Livewire yang ada; jangan memindahkan ulang tanpa audit referensi |
| Workflow kamera dan GPS | Implementasi inti sudah di-merge | Tetap perlu pengujian perangkat nyata melalui HTTPS, termasuk izin kamera/lokasi, orientasi, timeout, dan upload |
| Sesi absensi dan koreksi | Perubahan domain dan workflow sudah di-merge | Jaga keterkaitan sesi, bukti asli, timestamp efektif, koreksi, dan data legacy yang ambigu |
| Overtime | Workflow penjadwalan, konfirmasi, status, dan notifikasi sudah di-merge | Jangan menyatukan approval status dengan operational state |
| Master Client, Location, Project | Fondasi domain dan halaman admin sudah di-merge | Pertahankan aturan ownership dan status domain pada model/migration/test |
| UI Admin | Final consistency pass tercatat selesai | Implementasi source sudah masuk; visual QA browser lintas viewport belum dibuktikan oleh audit ini |
| UI Pegawai | Sebagian standardisasi dan audit responsif tersedia | Halaman /absensi memiliki audit khusus; perubahan UX kamera/GPS perlu dilakukan terpisah dan diuji pada perangkat |
| Production deployment | Belum selesai | Hosting/domain, staging, secret management, backup, monitoring, rollback, dan pilot test belum dikonfirmasi |


## 4. Status standardisasi komponen UI

Primitive berikut sudah tersedia di resources/views/components/ui dan memiliki test komponen yang relevan. Adopsi dilakukan berdasarkan kecocokan semantik; tidak semua halaman harus memakai primitive yang sama.

| Kelompok | Primitive | Status |
|---|---|---|
| Button/action | x-ui.button | Tersedia dan memiliki test; digunakan bertahap pada halaman Admin |
| Layout | x-ui.page, x-ui.page-header, x-ui.section | Tersedia dan memiliki test; container/header Admin telah distandardisasi |
| Card/surface | x-ui.card | Tersedia dan memiliki test; bukan pengganti universal untuk article, table, atau review list |
| Feedback | x-ui.flash-message, x-ui.empty-state | Tersedia dan memiliki test; adapter Admin tetap dipertahankan |
| Form controls | x-ui.field, x-ui.input, x-ui.select, x-ui.textarea, x-ui.checkbox | Tersedia dan memiliki test; dipakai pada form yang kontraknya sesuai |
| Status | x-ui.badge, x-admin.request-status-badge | Tersedia; status domain tetap dipetakan oleh enum/adapter |
| Metadata | x-ui.metadata-item | Tersedia dan memiliki test; digunakan untuk pasangan label/nilai yang bersifat deskriptif |

### Strategi komponen data

Belum ada keputusan untuk membuat satu komponen generik bagi semua collection, table, detail, atau form. Halaman menggunakan struktur yang sesuai dengan kontraknya:
- Native HTML table untuk data kronologis/komparatif yang membutuhkan header kolom.
- Flux table untuk interaksi Livewire seperti sorting dan pagination.
- Review list untuk antrean yang berorientasi membuka satu record.
- Card/article untuk ringkasan record.
- Expandable card Proyek memakai native details/summary.

Jangan membuat abstraksi tabel/data baru hanya untuk mengurangi markup. Audit kesamaan semantik, aksesibilitas, state, pagination, dan interaksi terlebih dahulu.

## 5. Verifikasi CI yang diketahui

CI terakhir yang terdokumentasi untuk PR #25 dijalankan pada commit branch sebelum merge, yaitu 7d75aad375dbbd23b3d87f26c529bc48ebf1a8f8, melalui [GitHub Actions run #932](https://github.com/leonsmith128/pt-sanmal/actions/runs/41522522835).

Hasil yang tercatat:

| Pemeriksaan | Hasil |
|---|---|
| Pint untuk PHP yang berubah | Lulus |
| Targeted PHPStan pada file aplikasi yang dikonfigurasi workflow | Lulus |
| MariaDB 11 | 214 passed, 1 skipped |
| SQLite | 213 passed, 2 skipped |
| Total test | 427 passed, 3 skipped |

**Batas interpretasi CI:**

- Angka di atas adalah hasil run #932 pada commit branch sebelum merge, bukan klaim bahwa commit dokumentasi ini atau setiap commit terbaru main telah diuji.
- Workflow menjalankan composer types:check untuk seluruh repository dengan continue-on-error: true. Perintah tersebut masih melaporkan baseline issue yang sudah ada; status step workflow tidak berarti seluruh repository bebas error PHPStan.
- Targeted PHPStan yang dikonfigurasi workflow lulus.
- Browser visual QA dan pengujian kamera/GPS pada perangkat nyata tidak termasuk bukti CI tersebut.

Setelah perubahan dokumentasi ini masuk, periksa GitHub Actions pada commit baru di main sebelum menyatakan CI terbaru lulus.

## 6. Risiko dan pekerjaan yang masih terbuka

### Wajib sebelum production

- [ ] Verifikasi CI pada HEAD main terbaru.
- [ ] Jalankan regression test untuk alur pegawai, admin, superadmin, absensi, lembur, permohonan, koreksi, riwayat, dan ekspor.
- [ ] Uji kamera, GPS, watermark, dan upload melalui HTTPS pada perangkat Android/iOS yang benar-benar akan digunakan.
- [ ] Audit otorisasi lintas role dan akses foto/lampiran.
- [ ] Pastikan storage bukti absensi privat dan aksesnya melalui kontrol otorisasi.
- [ ] Tetapkan hosting, domain, PHP runtime, database, dan konfigurasi HTTPS.
- [ ] Tetapkan SMTP, queue worker bila digunakan, scheduler, storage, dan log/monitoring.
- [ ] Siapkan backup database dan file, lalu uji pemulihannya.
- [ ] Siapkan deployment runbook dan rollback plan.
- [ ] Jalankan staging dan pilot test sebelum seluruh pegawai memakai aplikasi.

### Technical debt yang diketahui

- Repository-wide PHPStan masih memiliki baseline issue dan ditoleransi workflow. Perbaiki secara terpisah dan terukur; jangan menyembunyikannya dengan memperluas ignoreErrors atau menonaktifkan pemeriksaan.
- Audit UI tidak menggantikan pengujian visual pada browser, viewport, dark mode, maupun perangkat nyata.
- Halaman /absensi adalah alur kritis perangkat; perubahan UI di sana harus menjaga lifecycle kamera, GPS, upload, modal, dan Livewire state.
- Kebijakan kerja/penggajian yang belum diputuskan tidak boleh diasumsikan atau dihitung otomatis.

## 7. Urutan kerja yang disarankan

1. Main branch verification — pastikan merge dan CI pada HEAD terbaru.
2. Regression testing — jalankan test otomatis dan skenario manual berbasis role.
3. Security review — audit authorization, file access, upload, session, dan secrets.
4. Device QA — uji kamera/GPS pada perangkat dan jaringan nyata melalui HTTPS.
5. Environment readiness — tetapkan hosting, domain, database, email, storage, queue, dan scheduler.
6. Staging — deploy release candidate dengan konfigurasi mendekati production.
7. Backup/rollback rehearsal — buktikan backup dapat dipulihkan dan rollback dapat dilakukan.
8. Pilot release — gunakan kelompok kecil sebelum rilis penuh.

## 8. Aturan pembaruan dokumen

Perbarui dokumen ini ketika milestone di-merge, CI berubah, keputusan production ditetapkan, atau risiko diselesaikan. Cantumkan commit/PR dan bukti verifikasi; jangan mengubah status menjadi selesai hanya karena implementasi source sudah ada.
