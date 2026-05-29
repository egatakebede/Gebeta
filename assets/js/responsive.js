// ============================================
// RESPONSIVE BEHAVIOR
// ============================================

// Detect device type
const isMobile = window.innerWidth < 768;
const isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
const isDesktop = window.innerWidth >= 1024;

// Detect touch device
const isTouchDevice = () => {
    return (
        (typeof window !== 'undefined' &&
            ('ontouchstart' in window ||
                navigator.maxTouchPoints > 0 ||
                navigator.msMaxTouchPoints > 0))
    );
};

// Set custom VH variable to handle mobile viewport properly (handles keyboard and address bar)
const updateVh = () => {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
};

// Handle window resize
let initialHeight = window.innerHeight;
window.addEventListener('resize', () => {
    updateVh();
    const newIsMobile = window.innerWidth < 768;
    
    // Virtual keyboard detection: height shrinks significantly
    if (window.innerHeight < initialHeight * 0.8) {
        document.body.classList.add('keyboard-open');
    } else {
        initialHeight = window.innerHeight;
        document.body.classList.remove('keyboard-open');
    }

    if (newIsMobile !== isMobile) {
        // Viewport changed
        console.log('Viewport changed');
    }
});

// Safe area insets (for notch phones)
const getSafeAreaInsets = () => {
    const top = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--safe-area-inset-top'));
    const right = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--safe-area-inset-right'));
    const bottom = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--safe-area-inset-bottom'));
    const left = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--safe-area-inset-left'));
    
    return { top, right, bottom, left };
};

// Prevent zoom on input focus (iOS)
document.addEventListener('touchstart', function(event) {
    if (event.touches.length > 1) {
        event.preventDefault();
    }
}, { passive: false });

// Orientation change
window.addEventListener('orientationchange', () => {
    console.log('Orientation:', window.orientation);
});

// Scroll optimization
let ticking = false;
window.addEventListener('scroll', () => {
    if (!ticking) {
        window.requestAnimationFrame(() => {
            // Scroll event handling
            ticking = false;
        });
        ticking = true;
    }
}, { passive: true });

// Mobile menu toggle
const hamburger = document.querySelector('.hamburger');
if (hamburger) {
    hamburger.addEventListener('click', () => {
        const menu = document.querySelector('.mobile-menu');
        if (menu) {
            menu.classList.toggle('active');
        }
    });
}

// Prevent iOS bounce scroll
document.body.addEventListener('touchmove', function(e) {
    if (e.target.closest('.scrollable')) {
        return; // Allow scroll on .scrollable
    }
    // Prevent default bounce
    // e.preventDefault(); // This can break natural scrolling if used globally
}, { passive: true });

/**
 * Mobile-ready enhancements
 */
document.addEventListener('DOMContentLoaded', () => {
    // Add mobile-ready class to body for CSS hooks
    document.body.classList.add('mobile-ready');
    
    if (isTouchDevice()) {
        document.body.classList.add('touch-device');
    }
    
    // Initialize VH units
    updateVh();
});