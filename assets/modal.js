// assets/modal.js
// Gestion de la modal d'ajout d'événement depuis le calendrier.
// La modal est en overlay fixe (z-[9999]) défini dans base.html.twig.
// Le JS toglle simplement la classe "hidden".

function openModal() {
    const modal = document.getElementById('activity-modal');
    if (!modal) return;

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('activity-modal');
    const frame = document.getElementById('activity-modal-frame');
    if (!modal) return;

    modal.classList.add('hidden');
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
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
            credentials: 'same-origin',
        });

        // Mettre à jour le contenu de la modale (succès, doublon ou erreur)
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const remoteFrame = doc.querySelector('turbo-frame#activity-modal-frame');
        frame.innerHTML = remoteFrame ? remoteFrame.innerHTML : html;
    } catch (err) {
        form.submit();
    }
}, true);

// Fermer au clic sur le backdrop
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
