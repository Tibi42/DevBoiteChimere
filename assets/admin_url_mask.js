/**
 * Masquage des URLs dans la barre d'adresse du navigateur.
 *
 * Pour des raisons de confidentialité et d'ergonomie :
 *  - Les pages publiques listées dans PUBLIC_MASKED affichent "/" dans la barre d'adresse.
 *  - Les sous-pages admin (/admin/*) affichent "/admin".
 *
 * Utilise history.replaceState() (sans recharger la page) ; fonctionne avec
 * la navigation Turbo (turbo:load) et le chargement initial.
 *
 * Attention : ce masquage est purement cosmétique et ne sécurise pas les routes.
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
