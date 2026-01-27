document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('mobile-menu-btn');
    const drawer = document.getElementById('mobile-menu-drawer');
    const overlay = document.getElementById('mobile-menu-overlay');
    const content = document.getElementById('mobile-menu-content');
    const spans = btn?.querySelectorAll('span');
    let isOpen = false;

    if (!btn || !drawer || !overlay || !content) return;

    function toggleMenu(e) {
        if (e && e.stopPropagation) e.stopPropagation();
        isOpen = !isOpen;
        if (isOpen) {
            drawer.classList.remove('opacity-0', 'pointer-events-none');
            overlay.classList.add('opacity-100');
            content.classList.remove('translate-x-full');
            // Hamburger to X
            if (spans) {
                spans[0].classList.add('rotate-45', 'translate-y-2');
                spans[1].classList.add('opacity-0');
                spans[2].classList.add('-rotate-45', '-translate-y-1.5');
            }
            document.body.style.overflow = 'hidden';
        } else {
            drawer.classList.add('opacity-0', 'pointer-events-none');
            overlay.classList.remove('opacity-100');
            content.classList.add('translate-x-full');
            // X back to Hamburger
            if (spans) {
                spans[0].classList.remove('rotate-45', 'translate-y-2');
                spans[1].classList.remove('opacity-0');
                spans[2].classList.remove('-rotate-45', '-translate-y-1.5');
            }
            document.body.style.overflow = '';
        }
    }

    btn.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);
    document.querySelectorAll('.mobile-nav-link').forEach(link => {
        link.addEventListener('click', toggleMenu);
    });
});
