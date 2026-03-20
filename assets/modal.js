// assets/modal.js
// Gestion de la modal d'ajout d'événement depuis le calendrier.
// La modal wraps un <turbo-frame id="activity-modal-frame">.

function initModal() {
    const modal = document.getElementById('activity-modal');
    const frame = document.getElementById('activity-modal-frame');

    if (!modal || !frame) return;
    if (modal.dataset.modalInit) return;
    modal.dataset.modalInit = 'true';

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        // Vider le frame pour éviter le contenu périmé à la prochaine ouverture
        frame.innerHTML = '';
        frame.removeAttribute('src');
    }

    // Ouvrir quand le frame charge le formulaire
    frame.addEventListener('turbo:frame-load', openModal);

    // Fermer au clic sur le backdrop (l'overlay sombre)
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Fermer via les boutons data-modal-close (bubbling depuis le frame)
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-modal-close]')) {
            e.preventDefault();
            closeModal();
        }
    });

    // Fermer avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

document.addEventListener('DOMContentLoaded', initModal);
document.addEventListener('turbo:load', initModal);
