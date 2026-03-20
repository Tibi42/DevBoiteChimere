// assets/modal.js
// Gestion de la modal d'ajout d'événement depuis le calendrier.
// Mobile : carte inline au-dessus du calendrier (pas d'overlay).
// Desktop (lg+) : overlay fixe centré avec backdrop sombre.
// Chargement du formulaire via fetch() (indépendant de Turbo Frames).

const LG_BREAKPOINT = 1024;

function openModal() {
    const modal = document.getElementById('activity-modal');
    if (!modal) return;
    modal.classList.remove('hidden');

    if (window.innerWidth >= LG_BREAKPOINT) {
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    } else {
        modal.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function closeModal() {
    const modal = document.getElementById('activity-modal');
    const frame = document.getElementById('activity-modal-frame');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
    if (frame) {
        frame.innerHTML = '';
    }
}

// Clic sur un jour du calendrier → fetch le formulaire et l'injecter dans la modal
document.addEventListener('click', async (e) => {
    const link = e.target.closest('[data-modal-url]');
    if (!link) return;

    e.preventDefault();
    const url = link.getAttribute('data-modal-url');
    const frame = document.getElementById('activity-modal-frame');
    if (!frame) return;

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) {
            // Redirect vers login ou erreur → navigation classique
            window.location.href = url;
            return;
        }
        const html = await response.text();
        // Extraire le contenu du <turbo-frame> si présent, sinon tout le HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const remoteFrame = doc.querySelector('turbo-frame#activity-modal-frame');
        frame.innerHTML = remoteFrame ? remoteFrame.innerHTML : html;
        openModal();
    } catch (err) {
        // En cas d'erreur réseau, navigation classique
        window.location.href = url;
    }
});

// Fermer au clic sur le backdrop (desktop uniquement)
document.addEventListener('click', (e) => {
    const modal = document.getElementById('activity-modal');
    if (modal && e.target === modal) {
        closeModal();
    }
});

// Fermer via les boutons data-modal-close
document.addEventListener('click', (e) => {
    if (e.target.closest('[data-modal-close]')) {
        e.preventDefault();
        closeModal();
    }
});

// Fermer avec Escape
document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('activity-modal');
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
        closeModal();
    }
});
