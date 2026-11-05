/**
 * Alpine component untuk menyalin password sementara pegawai (halaman edit pegawai).
 *
 * Password tidak dirender pada HTML awal; ia diminta lewat endpoint khusus
 * saat tombol diklik, lalu disalin ke clipboard atau ditampilkan manual.
 */
export default function temporaryPasswordCopy({ url, csrf }) {
    return {
        copying: false,
        copied: false,
        manualPassword: '',
        message: '',

        async copyText(value) {
            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(value);

                    return true;
                } catch (error) {
                    // Lanjut ke fallback yang kompatibel dengan browser di bawah.
                }
            }

            const textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);

            let success = false;

            try {
                success = document.execCommand('copy');
            } finally {
                textarea.remove();
            }

            return success;
        },

        focusManualInput() {
            this.$refs.manualPasswordInput?.focus();
            this.$refs.manualPasswordInput?.select();
        },

        showManualFallback(password) {
            this.manualPassword = password;
            this.message = 'Clipboard tidak tersedia. Salin password dari kolom berikut secara manual.';
            this.$nextTick(() => this.focusManualInput());
        },

        async copyTemporaryPassword() {
            this.copying = true;
            this.copied = false;
            this.message = '';
            this.manualPassword = '';
            let retrievedPassword = '';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Password sementara tidak dapat diambil.');
                }

                const data = await response.json();

                if (typeof data.password !== 'string' || data.password.length === 0) {
                    throw new Error('Respons password tidak valid.');
                }

                retrievedPassword = data.password;
                const success = await this.copyText(retrievedPassword);

                if (success) {
                    this.copied = true;
                    this.message = 'Password berhasil disalin ke clipboard.';
                } else {
                    this.showManualFallback(retrievedPassword);
                }
            } catch (error) {
                if (retrievedPassword) {
                    this.showManualFallback(retrievedPassword);
                } else {
                    this.message = error.message || 'Password gagal diambil. Silakan coba kembali.';
                }
            } finally {
                this.copying = false;
            }
        },

        async copyManualPassword() {
            if (!this.manualPassword) {
                return;
            }

            try {
                const success = await this.copyText(this.manualPassword);

                if (success) {
                    this.copied = true;
                    this.message = 'Password berhasil disalin ke clipboard.';
                    this.manualPassword = '';
                } else {
                    this.focusManualInput();
                    this.message = 'Tekan Ctrl+C atau gunakan Salin pada menu browser.';
                }
            } catch (error) {
                this.focusManualInput();
                this.message = 'Tekan Ctrl+C atau gunakan Salin pada menu browser.';
            }
        },
    };
}
