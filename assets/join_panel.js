function initJoinPanel() {
    // Desktop panel
    const btn = document.getElementById('join-btn');
    const panel = document.getElementById('join-panel');

    if (btn && panel) {
        let open = false;

        function openPanel() {
            open = true;
            panel.style.maxHeight = panel.scrollHeight + 'px';
            btn.classList.add('ring-2', 'ring-white/30');
            setTimeout(() => panel.querySelector('input')?.focus(), 300);
        }

        function closePanel() {
            open = false;
            panel.style.maxHeight = '0';
            btn.classList.remove('ring-2', 'ring-white/30');
        }

        btn.addEventListener('click', () => open ? closePanel() : openPanel());
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && open) closePanel(); });
    }

    // Mobile panel (inside burger menu)
    const mobileBtn = document.getElementById('mobile-join-btn');
    const mobileForm = document.getElementById('mobile-join-form');

    if (mobileBtn && mobileForm) {
        let mobileOpen = false;

        function openMobile() {
            mobileOpen = true;
            mobileForm.style.maxHeight = mobileForm.scrollHeight + 'px';
            mobileBtn.classList.add('ring-2', 'ring-white/30');
            setTimeout(() => mobileForm.querySelector('input')?.focus(), 300);
        }

        function closeMobile() {
            mobileOpen = false;
            mobileForm.style.maxHeight = '0';
            mobileBtn.classList.remove('ring-2', 'ring-white/30');
        }

        mobileBtn.addEventListener('click', () => mobileOpen ? closeMobile() : openMobile());
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && mobileOpen) closeMobile(); });
    }
}

document.addEventListener('DOMContentLoaded', initJoinPanel);
document.addEventListener('turbo:load', initJoinPanel);
