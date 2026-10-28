import attendanceCamera from './components/attendance-camera.js';

// Daftarkan komponen sebelum Alpine (bawaan Livewire) mulai berjalan.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('attendanceCamera', attendanceCamera);
});

