function initMobileMenu() {
    const btn = document.getElementById('mobile-menu-btn');
    const drawer = document.getElementById('mobile-menu-drawer');

    if (!btn || !drawer) return;
    if (btn.dataset.menuInit) return;
    btn.dataset.menuInit = '1';

    const spans = btn.querySelectorAll('span');
    let isOpen = false;

    function open() {
        isOpen = true;
        drawer.classList.remove('pointer-events-none');
        drawer.classList.add('menu-open');
        spans[0]?.classList.add('rotate-45', 'translate-y-2');
        spans[1]?.classList.add('opacity-0', 'scale-x-0');
        spans[2]?.classList.add('-rotate-45', '-translate-y-2');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        isOpen = false;
        drawer.classList.remove('menu-open');
        drawer.classList.add('pointer-events-none');
        spans[0]?.classList.remove('rotate-45', 'translate-y-2');
        spans[1]?.classList.remove('opacity-0', 'scale-x-0');
        spans[2]?.classList.remove('-rotate-45', '-translate-y-2');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', (e) => { e.stopPropagation(); isOpen ? close() : open(); });
    document.getElementById('mobile-menu-overlay')?.addEventListener('click', close);
    document.querySelectorAll('.mobile-nav-link').forEach(link => {
        link.addEventListener('click', close);
    });
}

document.addEventListener('DOMContentLoaded', initMobileMenu);
document.addEventListener('turbo:load', initMobileMenu);
