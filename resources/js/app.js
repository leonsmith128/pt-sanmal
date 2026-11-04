import attendanceCamera from './components/attendance-camera.js';

// Daftarkan komponen sebelum Alpine (bawaan Livewire) mulai berjalan.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('attendanceCamera', attendanceCamera);
});


// Keep the desktop Flux inset panel's scrollbar subtle and visible only during use.
const initializeMainScrollbar = () => {
    const main = document.querySelector('[data-sanmal-main-scroll]');

    if (!main || main.dataset.sanmalScrollbarInitialized === 'true') {
        return;
    }

    main.dataset.sanmalScrollbarInitialized = 'true';
    main.classList.add('sanmal-main-scroll-ready');

    let hideTimeout;

    main.addEventListener('scroll', () => {
        main.classList.add('sanmal-main-scroll-active');

        window.clearTimeout(hideTimeout);

        hideTimeout = window.setTimeout(() => {
            main.classList.remove('sanmal-main-scroll-active');
        }, 850);
    }, { passive: true });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMainScrollbar, { once: true });
} else {
    initializeMainScrollbar();
}

document.addEventListener('livewire:navigated', initializeMainScrollbar);
