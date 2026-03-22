/**
 * Masks route URLs in the browser address bar.
 * - Public pages → /
 * - Admin sub-pages (/admin/*) → /admin
 */
const PUBLIC_MASKED = [
    '/jds', '/jdr', '/gn', '/association',
    '/nos/soiree/heb', '/nos/soiree/biheb', '/nos/soiree/mensuelle',
    '/evenements', '/contact', '/mentions-legales',
    '/qui-sommes-nous', '/nouvelles', '/societes',
    '/mon-espace',
];

function maskUrl() {
    const path = window.location.pathname;

    if (PUBLIC_MASKED.includes(path)) {
        window.history.replaceState(null, '', '/');
    } else if (path.startsWith('/admin/')) {
        window.history.replaceState(null, '', '/admin');
    }
}

document.addEventListener('turbo:load', maskUrl);

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    maskUrl();
} else {
    document.addEventListener('DOMContentLoaded', maskUrl);
}
