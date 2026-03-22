// assets/modal.js
// Gestion de la modal d'ajout d'événement depuis le calendrier.
// Mobile : carte inline au-dessus du calendrier.
// Desktop (lg+) : overlay fixe centré — la modal est déplacée dans <body>
// pour échapper au stacking context du hero.

const LG_BREAKPOINT = 1024;

// Sauvegarde la position d'origine pour le mode mobile
let originalParent = null;
let originalNext = null;

function openModal() {
    const modal = document.getElementById('activity-modal');
    if (!modal) return;

    if (window.innerWidth >= LG_BREAKPOINT) {
        // Desktop : déplacer dans <body> et afficher en overlay fixe
        originalParent = modal.parentNode;
        originalNext = modal.nextSibling;
        document.body.appendChild(modal);
        modal.className = 'fixed inset-0 z-[9999] bg-black/70 flex items-start justify-center p-4 overflow-y-auto';
        document.body.style.overflow = 'hidden';
    } else {
        // Mobile : afficher inline au-dessus du calendrier
        modal.classList.remove('hidden');
        modal.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function closeModal() {
    const modal = document.getElementById('activity-modal');
    const frame = document.getElementById('activity-modal-frame');
    if (!modal) return;

    if (window.innerWidth >= LG_BREAKPOINT && originalParent) {
        // Desktop : remettre à sa place d'origine
        originalParent.insertBefore(modal, originalNext);
        originalParent = null;
        originalNext = null;
    }

    // Réinitialiser les classes
    modal.className = 'hidden px-8 mb-6';
    document.body.style.overflow = '';

    if (frame) {
        frame.innerHTML = '';
    }
}

// Clic sur un jour du calendrier → fetch le formulaire
document.addEventListener('click', async (e) => {
    const link = e.target.closest('[data-modal-url]');
    if (!link) return;

    e.preventDefault();
    const url = link.getAttribute('data-modal-url');
    const frame = document.getElementById('activity-modal-frame');
    if (!frame) return;

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            window.location.href = url;
            return;
        }
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const remoteFrame = doc.querySelector('turbo-frame#activity-modal-frame');
        frame.innerHTML = remoteFrame ? remoteFrame.innerHTML : html;
        openModal();
    } catch (err) {
        window.location.href = url;
    }
});

// Soumission du formulaire d'inscription en AJAX (reste dans la modale)
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.classList.contains('modal-inscription-form')) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    const frame = document.getElementById('activity-modal-frame');
    if (!frame) return;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
        });

        // Mettre à jour le contenu de la modale (succès, doublon ou erreur)
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const remoteFrame = doc.querySelector('turbo-frame#activity-modal-frame');
        frame.innerHTML = remoteFrame ? remoteFrame.innerHTML : html;

        // S'assurer que l'overlay reste visible après la mise à jour du contenu
        const modal = document.getElementById('activity-modal');
        if (modal && window.innerWidth >= LG_BREAKPOINT) {
            if (!modal.classList.contains('fixed')) {
                modal.className = 'fixed inset-0 z-[9999] bg-black/70 flex items-start justify-center p-4 overflow-y-auto';
                document.body.style.overflow = 'hidden';
            }
        } else if (modal && !modal.classList.contains('fixed')) {
            modal.classList.remove('hidden');
        }
    } catch (err) {
        form.submit();
    }
}, true);

// Fermer au clic sur le backdrop (desktop)
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
