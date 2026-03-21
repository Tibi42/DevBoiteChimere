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
        btn.classList.add('hidden');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        isOpen = false;
        drawer.classList.remove('menu-open');
        drawer.classList.add('pointer-events-none');
        btn.classList.remove('hidden');
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
