/**
 * Initialise le carrousel hero de la page d'accueil.
 *
 * Fonctionnement :
 *  - Clone le premier et le dernier slide pour créer un effet de boucle infinie.
 *  - Avance automatiquement toutes les 10 secondes (auto-play).
 *  - Met en pause si la souris survole le carrousel ou s'il n'est plus visible
 *    (IntersectionObserver → économise le CPU).
 *  - Supporte les gestes de balayage (swipe) sur mobile.
 *  - Nettoie les timers et observers avant chaque navigation Turbo (turbo:before-cache).
 *  - Garde anti-double-init via dataset.carouselInit.
 */
function initCarousel() {
    const carousel = document.getElementById('carousel');
    const container = document.getElementById('carousel-container');
    const dots = document.querySelectorAll('.carousel-events-dot');

    if (!carousel || !container || carousel.children.length === 0) return;

    // Prevent double initialization
    if (carousel.dataset.carouselInit) return;
    carousel.dataset.carouselInit = 'true';

    const originalSlides = Array.from(carousel.children);
    const totalOriginalSlides = originalSlides.length;

    // Clone slides for infinite loop
    const firstClone = originalSlides[0].cloneNode(true);
    const lastClone = originalSlides[totalOriginalSlides - 1].cloneNode(true);

    carousel.appendChild(firstClone);
    carousel.insertBefore(lastClone, originalSlides[0]);

    let currentIndex = 1;
    let isTransitioning = false;
    let autoPlayInterval;
    let safetyInterval;

    /** Déplace instantanément le carrousel sur la slide à l'index donné (sans transition). */
    const setPosition = (index) => {
        carousel.style.transform = `translateX(-${index * 100}%)`;
    };

    /** Déplace le carrousel vers l'index donné et met à jour les indicateurs (dots). */
    const updateCarousel = (index, useTransition = true) => {
        carousel.style.transition = useTransition ? 'transform 1000ms cubic-bezier(0.4, 0, 0.2, 1)' : 'none';
        isTransitioning = useTransition;
        setPosition(index);

        // Update dots
        const dotIndex = (index - 1 + totalOriginalSlides) % totalOriginalSlides;
        dots.forEach((dot, i) => {
            const isActive = i === dotIndex;
            dot.classList.toggle('bg-custom-orange', isActive);
            dot.classList.toggle('w-10', isActive);
            dot.classList.toggle('bg-white/50', !isActive);
            dot.classList.toggle('w-3', !isActive);
        });
    };

    /**
     * Vérifie si on se trouve sur un slide cloné et repositionne silencieusement
     * sur le slide original correspondant pour créer l'effet de boucle infinie.
     */
    const checkBoundaries = () => {
        if (currentIndex === 0) {
            currentIndex = totalOriginalSlides;
            updateCarousel(currentIndex, false);
        } else if (currentIndex === totalOriginalSlides + 1) {
            currentIndex = 1;
            updateCarousel(currentIndex, false);
        }
    };

    carousel.addEventListener('transitionend', () => {
        isTransitioning = false;
        checkBoundaries();
    });

    const startAutoPlay = () => {
        stopAutoPlay();
        autoPlayInterval = setInterval(() => {
            if (!isTransitioning) {
                currentIndex++;
                if (currentIndex > totalOriginalSlides + 1) {
                    currentIndex = 1;
                    updateCarousel(currentIndex, false);
                } else {
                    updateCarousel(currentIndex);
                }
            }
        }, 10000);
    };

    const stopAutoPlay = () => clearInterval(autoPlayInterval);

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            if (isTransitioning) return;
            currentIndex = index + 1;
            updateCarousel(currentIndex);
            startAutoPlay();
        });
    });

    // Swipe Support
    let touchStartX = 0;
    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        stopAutoPlay();
    }, { passive: true });

    carousel.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].clientX;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50 && !isTransitioning) {
            currentIndex += diff > 0 ? 1 : -1;
            if (currentIndex < 0) currentIndex = totalOriginalSlides;
            if (currentIndex > totalOriginalSlides + 1) currentIndex = 1;
            updateCarousel(currentIndex);
        }
        startAutoPlay();
    }, { passive: true });

    container.addEventListener('mouseenter', stopAutoPlay);
    container.addEventListener('mouseleave', startAutoPlay);

    // Pause quand le carousel n'est pas visible (économise CPU)
    const visibilityObserver = new IntersectionObserver((entries) => {
        entries[0].isIntersecting ? startAutoPlay() : stopAutoPlay();
    }, { threshold: 0.1 });
    visibilityObserver.observe(container);

    // Initial setup
    updateCarousel(currentIndex, false);
    startAutoPlay();

    // Safety reset (nettoyable)
    safetyInterval = setInterval(() => {
        if (isTransitioning) {
            isTransitioning = false;
            checkBoundaries();
        }
        if (currentIndex < 0 || currentIndex > totalOriginalSlides + 1) {
            currentIndex = 1;
            updateCarousel(currentIndex, false);
        }
    }, 3000);

    // Cleanup on Turbo navigation
    document.addEventListener('turbo:before-cache', () => {
        stopAutoPlay();
        clearInterval(safetyInterval);
        visibilityObserver.disconnect();
        carousel.removeAttribute('data-carousel-init');
    }, { once: true });
}

document.addEventListener('DOMContentLoaded', initCarousel);
document.addEventListener('turbo:load', initCarousel);
