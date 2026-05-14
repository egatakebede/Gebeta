document.addEventListener('DOMContentLoaded', function () {
    const loginModal = document.getElementById('login-modal');
    const registerModal = document.getElementById('register-modal');
    const overlay = document.getElementById('modal-overlay');

    function openModal(modal) {
        modal.classList.remove('hidden');
        overlay.classList.remove('hidden');
    }

    function closeModal(modal) {
        modal.classList.add('hidden');
        overlay.classList.add('hidden');
    }

    document.getElementById('open-login').addEventListener('click', function () {
        openModal(loginModal);
    });

    document.getElementById('open-register').addEventListener('click', function () {
        openModal(registerModal);
    });

    document.getElementById('close-login').addEventListener('click', function () {
        closeModal(loginModal);
    });

    document.getElementById('close-register').addEventListener('click', function () {
        closeModal(registerModal);
    });

    overlay.addEventListener('click', function () {
        closeModal(loginModal);
        closeModal(registerModal);
    });

    document.querySelectorAll('.switch-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.dataset.target === 'register') {
                closeModal(loginModal);
                openModal(registerModal);
            } else {
                closeModal(registerModal);
                openModal(loginModal);
            }
        });
    });

    const registerEmail = document.querySelector('#register-form input[name="email"]');
    if (registerEmail) {
        registerEmail.addEventListener('blur', function () {
            const email = registerEmail.value.trim();
            if (!email) return;
            fetch('api/check-email.php?email=' + encodeURIComponent(email))
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        registerEmail.style.borderColor = '#48C479';
                    } else {
                        registerEmail.style.borderColor = '#E53935';
                    }
                });
        });
    }

    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    let searchTimer;

    function renderSearchResults(results) {
        if (!searchResults) return;
        if (!results.length) {
            searchResults.innerHTML = '<div class="empty-state">No restaurants found.</div>';
            return;
        }
        searchResults.innerHTML = results.map(r => {
            return `<a class="restaurant-card" href="/customer/restaurant.php?id=${r.id}"><div><h3>${r.name}</h3><p>${r.cuisine_type} • ${r.location}</p><div class="restaurant-stats"><span>⭐ ${parseFloat(r.rating).toFixed(1)}</span><span>38 mins</span><span>300 Birr</span></div></div></a>`;
        }).join('');
    }

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const query = searchInput.value.trim();
            if (!query) {
                searchResults.innerHTML = '';
                return;
            }
            searchTimer = setTimeout(() => {
                fetch('/api/search.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        renderSearchResults(data.results || []);
                    });
            }, 300);
        });
    }
});
