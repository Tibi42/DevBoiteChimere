document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.getElementById('carousel');
    const container = document.getElementById('carousel-container');
    const dots = document.querySelectorAll('.carousel-events-dot');

    if (!carousel || !container) return;

    const originalSlides = Array.from(carousel.children);
    const totalOriginalSlides = originalSlides.length;

    // Clone slides for infinite loop
    const firstClone = originalSlides[0].cloneNode(true);
    const lastClone = originalSlides[totalOriginalSlides - 1].cloneNode(true);

    carousel.appendChild(firstClone);
    carousel.insertBefore(lastClone, originalSlides[0]);

    let currentIndex = 1; // Start at the real first slide (index 1 because of prepended clone)
    let isTransitioning = false;

    // Initial position
    carousel.style.transform = `translateX(-${currentIndex * 100}%)`;

    function updateCarousel(index, useTransition = true) {
        if (useTransition) {
            carousel.style.transition = 'transform 1000ms cubic-bezier(0.4, 0, 0.2, 1)';
            isTransitioning = true;
        } else {
            carousel.style.transition = 'none';
            isTransitioning = false;
        }
        carousel.style.transform = `translateX(-${index * 100}%)`;

        // Map index back to correct dot index
        let dotIndex = index - 1;
        if (index === 0) dotIndex = totalOriginalSlides - 1;
        if (index === totalOriginalSlides + 1) dotIndex = 0;

        dots.forEach((dot, i) => {
            if (i === dotIndex) {
                dot.classList.add('bg-custom-orange', 'w-10');
                dot.classList.remove('bg-white/50', 'w-3');
            } else {
                dot.classList.remove('bg-custom-orange', 'w-10');
                dot.classList.add('bg-white/50', 'w-3');
            }
        });
    }

    carousel.addEventListener('transitionend', () => {
        isTransitioning = false;
        // Seamless warp if at clones
        if (currentIndex === 0) {
            currentIndex = totalOriginalSlides;
            updateCarousel(currentIndex, false);
        } else if (currentIndex === totalOriginalSlides + 1) {
            currentIndex = 1;
            updateCarousel(currentIndex, false);
        }
    });

    // Safety check to reset isTransitioning if it gets stuck
    setInterval(() => {
        if (isTransitioning) {
            isTransitioning = false;
        }
    }, 2000);

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            if (isTransitioning) return;
            currentIndex = index + 1;
            updateCarousel(currentIndex);
            startAutoPlay();
        });
    });

    let autoPlayInterval;
    function startAutoPlay() {
        clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(() => {
            if (!isTransitioning) {
                currentIndex++;
                updateCarousel(currentIndex);
            }
        }, 10000);
    }

    function stopAutoPlay() {
        clearInterval(autoPlayInterval);
    }

    // Swipe Support
    let touchStartX = 0;
    let touchEndX = 0;

    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        stopAutoPlay();
    }, { passive: true });

    carousel.addEventListener('touchmove', (e) => { }, { passive: true });

    carousel.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].clientX;
        const swipeThreshold = 50;
        if (touchStartX - touchEndX > swipeThreshold) {
            // Swipe Left -> Next
            if (!isTransitioning) {
                currentIndex++;
                updateCarousel(currentIndex);
            }
        } else if (touchEndX - touchStartX > swipeThreshold) {
            // Swipe Right -> Prev
            if (!isTransitioning) {
                currentIndex--;
                updateCarousel(currentIndex);
            }
        }
        startAutoPlay();
    }, { passive: true });

    carousel.addEventListener('touchcancel', () => {
        startAutoPlay();
    }, { passive: true });

    container.addEventListener('mouseenter', stopAutoPlay);
    container.addEventListener('mouseleave', startAutoPlay);

    startAutoPlay();
    // Sync dots initial state
    updateCarousel(currentIndex, false);
});
