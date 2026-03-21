function initReveal() {
    const revealEls = document.querySelectorAll('.reveal');

    // Éléments déjà visibles au chargement : affichage immédiat (sans délai d'IntersectionObserver)
    function setVisibleInstantly(el) {
        el.classList.add('reveal-instant', 'active');
        requestAnimationFrame(() => {
            el.classList.remove('reveal-instant');
        });
    }

    function markVisibleReveals() {
        revealEls.forEach((el) => {
            if (el.classList.contains('active')) return;
            const rect = el.getBoundingClientRect();
            const hasSize = rect.width > 0 && rect.height > 0;
            const inViewport = rect.top < window.innerHeight + 50 && rect.bottom > -50;
            if (hasSize && inViewport) {
                setVisibleInstantly(el);
            }
        });
    }

    markVisibleReveals();
    requestAnimationFrame(markVisibleReveals);

    // Intersection Observer pour le contenu qui entre au scroll
    const observerOptions = {
        threshold: 0.05,
        rootMargin: '0px 0px -30px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, observerOptions);

    revealEls.forEach((el) => {
        if (!el.classList.contains('active')) {
            observer.observe(el);
        }
    });
}

document.addEventListener('DOMContentLoaded', initReveal);
document.addEventListener('turbo:load', initReveal);
