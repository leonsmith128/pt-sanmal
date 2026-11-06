import attendanceCamera from './components/attendance-camera.js';
import temporaryPasswordCopy from './components/temporary-password-copy.js';

// Daftarkan komponen sebelum Alpine (bawaan Livewire) mulai berjalan.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('attendanceCamera', attendanceCamera);
    window.Alpine.data('temporaryPasswordCopy', temporaryPasswordCopy);
});
