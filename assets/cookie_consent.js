/**
 * Gestion de la bannière de consentement aux cookies.
 *
 * Stocke le choix de l'utilisateur (accepted / refused) dans localStorage.
 * La bannière est masquée si un choix a déjà été enregistré.
 * Un lien dans le footer avec l'attribut [data-cookie-settings] permet
 * de rouvrir la bannière pour modifier son choix.
 */

const COOKIE_KEY = 'cookie_consent';

function showBanner() {
    const banner = document.getElementById('cookie-banner');
    if (banner) {
        banner.classList.remove('hidden');
    }
}

function hideBanner() {
    const banner = document.getElementById('cookie-banner');
    if (banner) {
        banner.classList.add('hidden');
    }
}

function initCookieBanner() {
    const acceptBtn = document.getElementById('cookie-accept');
    const refuseBtn = document.getElementById('cookie-refuse');

    if (acceptBtn) {
        acceptBtn.addEventListener('click', () => {
            localStorage.setItem(COOKIE_KEY, 'accepted');
            hideBanner();
        });
    }

    if (refuseBtn) {
        refuseBtn.addEventListener('click', () => {
            localStorage.setItem(COOKIE_KEY, 'refused');
            hideBanner();
        });
    }

    if (!localStorage.getItem(COOKIE_KEY)) {
        showBanner();
    }
}

// Init on Turbo page load (fires on initial load + every Turbo visit)
document.addEventListener('turbo:load', initCookieBanner);

// Fallback: init immediately if DOM is already ready
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(initCookieBanner, 100);
} else {
    document.addEventListener('DOMContentLoaded', () => setTimeout(initCookieBanner, 100));
}

// Allow reopening from footer link
document.addEventListener('click', (e) => {
    if (e.target.closest('[data-cookie-settings]')) {
        e.preventDefault();
        localStorage.removeItem(COOKIE_KEY);
        showBanner();
    }
});
