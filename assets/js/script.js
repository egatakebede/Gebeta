/* ── Toast ── */
function showToast(message, type = 'info') {
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = message;
    document.body.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => {
        t.classList.remove('show');
        setTimeout(() => t.remove(), 350);
    }, 3000);
}

/* ── Bottom-sheet modal ── */
function openModal(id) {
    const overlay = document.getElementById('modal-overlay');
    const sheet   = document.getElementById(id);
    if (!sheet) return;
    overlay.classList.add('visible');
    sheet.classList.add('visible');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => requestAnimationFrame(() => {
        overlay.classList.add('active');
        sheet.classList.add('active');
    }));
}

function closeModal(id) {
    const overlay = document.getElementById('modal-overlay');
    const sheet   = document.getElementById(id);
    if (!sheet) return;
    overlay.classList.remove('active');
    sheet.classList.remove('active');
    setTimeout(() => {
        overlay.classList.remove('visible');
        sheet.classList.remove('visible');
        document.body.style.overflow = '';
    }, 350);
}

/* Swipe-down to close */
function enableSwipeClose(sheetId) {
    const sheet = document.getElementById(sheetId);
    if (!sheet) return;
    let startY = 0;
    sheet.addEventListener('touchstart', e => { startY = e.touches[0].clientY; }, { passive: true });
    sheet.addEventListener('touchmove', e => {
        const diff = e.touches[0].clientY - startY;
        if (diff > 0) sheet.style.transform = `translateY(${diff}px)`;
    }, { passive: true });
    sheet.addEventListener('touchend', e => {
        const diff = e.changedTouches[0].clientY - startY;
        if (diff > 100) {
            sheet.style.transform = '';
            closeModal(sheetId);
        } else {
            sheet.style.transform = '';
        }
    });
}

/* ── Cart badge ── */
function updateCartBadge(count) {
    document.querySelectorAll('.cart-count').forEach(el => {
        el.textContent = count;
        el.classList.remove('bounce');
        void el.offsetWidth;
        el.classList.add('bounce');
    });
    // also update plain text cart links
    document.querySelectorAll('[data-cart-link]').forEach(el => {
        el.textContent = count > 0 ? `🛒 Cart (${count})` : '🛒 Cart';
    });
}

/* ── Lazy images ── */
function initLazyImages() {
    const imgs = document.querySelectorAll('img[data-src]');
    if (!imgs.length) return;
    const obs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                obs.unobserve(img);
            }
        });
    });
    imgs.forEach(img => { img.classList.add('lazy'); obs.observe(img); });
}

/* ── Live search (debounced) ── */
function initLiveSearch() {
    const input   = document.getElementById('search-input');
    const results = document.getElementById('search-results');
    if (!input || !results) return;

    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (!q) { results.innerHTML = ''; return; }

        results.innerHTML = '<div class="empty-state">Searching…</div>';
        timer = setTimeout(async () => {
            try {
                const res  = await fetch(`/api/search.php?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                const list = data.results || [];
                results.innerHTML = list.length
                    ? list.map(r => `<a class="restaurant-card" href="/customer/restaurant.php?id=${r.id}">
                        <div class="restaurant-image">🍽️</div>
                        <div class="restaurant-meta">
                            <h3>${r.name}</h3>
                            <p>${r.cuisine_type} · ${r.location}</p>
                            <div class="restaurant-stats"><span>⭐ ${parseFloat(r.rating).toFixed(1)}</span><span>38 mins</span></div>
                        </div></a>`).join('')
                    : '<div class="empty-state">No restaurants found.</div>';
            } catch { results.innerHTML = ''; }
        }, 300);
    });
}

/* ── Real-time email check ── */
function initEmailCheck() {
    const input = document.querySelector('#register-modal input[name="email"], #register-form input[name="email"]');
    if (!input) return;
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const email = input.value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                input.style.borderColor = 'var(--error-red)';
                return;
            }
            try {
                const res  = await fetch(`/api/check-email.php?email=${encodeURIComponent(email)}`);
                const data = await res.json();
                input.style.borderColor = data.available ? 'var(--success-green)' : 'var(--error-red)';
            } catch { /* ignore */ }
        }, 400);
    });
}

/* ── Skeleton helpers ── */
function skeletonCard() {
    return `<div class="skeleton-card">
        <div class="skeleton skeleton-image"></div>
        <div class="skeleton-lines">
            <div class="skeleton skeleton-text"></div>
            <div class="skeleton skeleton-text short"></div>
        </div>
    </div>`;
}

/* ── Order status polling ── */
function startOrderTracking(orderId) {
    const interval = setInterval(async () => {
        try {
            const res  = await fetch(`/api/order-status.php?id=${orderId}`);
            const data = await res.json();
            if (!data.status) return;

            document.querySelectorAll('.timeline-item').forEach(item => {
                if (item.dataset.status === data.status) {
                    item.classList.add('active');
                }
            });

            const trackTitle = document.querySelector('.track-card h2');
            if (trackTitle) trackTitle.textContent = data.label || data.status;

            if (data.status === 'delivered') {
                clearInterval(interval);
                showToast('Order delivered! 🎉', 'success');
            }
        } catch { /* ignore */ }
    }, 10000);
}

/* ── Pull-to-refresh ── */
function initPullToRefresh(onRefresh) {
    const indicator = document.querySelector('.pull-refresh');
    if (!indicator) return;
    let startY = 0, pulling = false;

    document.addEventListener('touchstart', e => {
        if (window.scrollY === 0) { startY = e.touches[0].clientY; pulling = true; }
    }, { passive: true });

    document.addEventListener('touchmove', e => {
        if (!pulling) return;
        const dist = Math.min(e.touches[0].clientY - startY, 100);
        if (dist > 0) {
            indicator.style.height = dist + 'px';
            indicator.textContent = dist >= 80 ? '↻' : '↓';
        }
    }, { passive: true });

    document.addEventListener('touchend', async () => {
        if (!pulling) return;
        pulling = false;
        if (parseInt(indicator.style.height) >= 80) {
            indicator.textContent = '↻';
            await onRefresh();
            showToast('Updated!', 'success');
        }
        indicator.style.height = '0';
    });
}

/* ── PWA service worker ── */
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}

/* ── DOMContentLoaded bootstrap ── */
document.addEventListener('DOMContentLoaded', () => {
    /* Modal triggers */
    document.getElementById('open-login')    ?.addEventListener('click', () => openModal('login-modal'));
    document.getElementById('open-register') ?.addEventListener('click', () => openModal('register-modal'));
    document.getElementById('close-login')   ?.addEventListener('click', () => closeModal('login-modal'));
    document.getElementById('close-register')?.addEventListener('click', () => closeModal('register-modal'));

    const overlay = document.getElementById('modal-overlay');
    overlay?.addEventListener('click', () => {
        closeModal('login-modal');
        closeModal('register-modal');
    });

    document.querySelectorAll('.switch-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.dataset.target === 'register') {
                closeModal('login-modal');
                openModal('register-modal');
            } else {
                closeModal('register-modal');
                openModal('login-modal');
            }
        });
    });

    enableSwipeClose('login-modal');
    enableSwipeClose('register-modal');

    initLiveSearch();
    initEmailCheck();
    initLazyImages();

    /* Order tracking auto-start */
    const trackEl = document.querySelector('[data-order-id]');
    if (trackEl) startOrderTracking(trackEl.dataset.orderId);
});
