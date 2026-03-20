function initCarousel() {
    const carousel = document.getElementById('carousel');
    const container = document.getElementById('carousel-container');
    const dots = document.querySelectorAll('.carousel-events-dot');

    if (!carousel || !container || carousel.children.length === 0) return;

    // Anti-double-init (DOMContentLoaded + turbo:load peuvent tous deux s'exécuter)
    if (carousel.dataset.carouselInit === '1') return;
    carousel.dataset.carouselInit = '1';

    // Si Turbo a déjà chargé une ancienne version du DOM, on évite d'empiler des intervalles.
    window.__carouselIntervals = window.__carouselIntervals || {};
    const prev = window.__carouselIntervals['home'];
    if (prev) {
        if (prev.autoPlayInterval) clearInterval(prev.autoPlayInterval);
        if (prev.safetyInterval) clearInterval(prev.safetyInterval);
    }

    const originalSlides = Array.from(carousel.children);
    const totalOriginalSlides = originalSlides.length;
    const firstRealIndex = 1; // index du 1er slide original (après clones)
    const lastRealIndex = totalOriginalSlides; // index du dernier slide original
    const lastCloneIndex = totalOriginalSlides + 1; // slide clone à la fin (pour la boucle infinie)
    const firstCloneIndex = 0; // slide clone au début

    // Clone slides for infinite loop
    const firstClone = originalSlides[0].cloneNode(true);
    const lastClone = originalSlides[totalOriginalSlides - 1].cloneNode(true);

    carousel.appendChild(firstClone);
    carousel.insertBefore(lastClone, originalSlides[0]);

    let currentIndex = 1;
    let isTransitioning = false;
    let autoPlayInterval = null;
    let safetyInterval = null;

    const setPosition = (index) => {
        carousel.style.transform = `translateX(-${index * 100}%)`;
    };

    // Si jamais `transitionend` est manqué (tab arrière-plan, throttling, etc),
    // on évite que `currentIndex` "dérive" et sorte des bornes.
    const normalizeIndex = () => {
        if (currentIndex < firstCloneIndex) {
            currentIndex = lastRealIndex;
            updateCarousel(currentIndex, false);
        } else if (currentIndex > lastCloneIndex) {
            currentIndex = firstRealIndex;
            updateCarousel(currentIndex, false);
        }
    };

    const updateCarousel = (index, useTransition = true) => {
        // Clamp défensif: empêche translateX de sortir des rails si on a dérivé.
        if (index < firstCloneIndex) index = lastRealIndex;
        if (index > lastCloneIndex) index = firstRealIndex;

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

    carousel.addEventListener('transitionend', (event) => {
        if (event && event.propertyName && event.propertyName !== 'transform') return;
        isTransitioning = false;
        if (currentIndex === 0) {
            currentIndex = totalOriginalSlides;
            updateCarousel(currentIndex, false);
        } else if (currentIndex === lastCloneIndex) {
            currentIndex = 1;
            updateCarousel(currentIndex, false);
        }
    });

    const startAutoPlay = () => {
        stopAutoPlay();
        autoPlayInterval = setInterval(() => {
            if (!isTransitioning) {
                currentIndex++;
                // Si l’événement `transitionend` a été raté, on peut dépasser le dernier clone.
                // On recale immédiatement pour éviter un translateX "hors écran".
                if (currentIndex > lastCloneIndex) {
                    currentIndex = firstRealIndex;
                    updateCarousel(currentIndex, false);
                } else {
                    updateCarousel(currentIndex);
                }
            }
        }, 10000);
    };

    const stopAutoPlay = () => {
        if (autoPlayInterval) clearInterval(autoPlayInterval);
        autoPlayInterval = null;
    };

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
    let suppressClick = false;
    let suppressClickTimeout;

    // Quand on swipe, le navigateur peut quand même déclencher un "click" sur l'ancre.
    // On bloque alors la navigation pour ne déclencher le lien qu'en cas de simple tap.
    carousel.addEventListener('click', (e) => {
        if (!suppressClick) return;
        e.preventDefault();
        e.stopPropagation();
        suppressClick = false;
    });

    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        stopAutoPlay();
    }, { passive: true });

    carousel.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].clientX;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50 && !isTransitioning) {
            currentIndex += diff > 0 ? 1 : -1;
            normalizeIndex();
            updateCarousel(currentIndex);
            suppressClick = true;
            clearTimeout(suppressClickTimeout);
            suppressClickTimeout = setTimeout(() => { suppressClick = false; }, 300);
        }
        startAutoPlay();
    }, { passive: true });

    container.addEventListener('mouseenter', stopAutoPlay);
    container.addEventListener('mouseleave', startAutoPlay);

    // Initial setup
    updateCarousel(currentIndex, false);
    startAutoPlay();
    window.__carouselIntervals['home'] = { autoPlayInterval, safetyInterval: null };

    // Safety reset
    safetyInterval = setInterval(() => {
        if (isTransitioning) isTransitioning = false;
        normalizeIndex();
    }, 3000);
    window.__carouselIntervals['home'].safetyInterval = safetyInterval;
}

document.addEventListener('DOMContentLoaded', initCarousel);
document.addEventListener('turbo:load', initCarousel);

