/**
 * Panneau « NOUS REJOINDRE » (desktop + mobile).
 * Garde anti double-init : DOMContentLoaded + turbo:load ne doivent pas empiler les listeners
 * (sinon un clic peut ouvrir puis refermer dans le même tick).
 */

let escapeHandlerInstalled = false;

function installEscapeHandlerOnce() {
    if (escapeHandlerInstalled) {
        return;
    }
    escapeHandlerInstalled = true;
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') {
            return;
        }
        const panel = document.getElementById('join-panel');
        const joinBtn = document.getElementById('join-btn');
        const mobileForm = document.getElementById('mobile-join-form');
        const mobileJoinBtn = document.getElementById('mobile-join-btn');

        const desktopOpen =
            panel &&
            joinBtn &&
            panel.style.maxHeight &&
            panel.style.maxHeight !== '0' &&
            panel.style.maxHeight !== '0px';
        if (desktopOpen) {
            panel.style.maxHeight = '0';
            joinBtn.classList.remove('ring-2', 'ring-white/30');
        }

        const mobileOpen =
            mobileForm &&
            mobileJoinBtn &&
            mobileForm.style.maxHeight &&
            mobileForm.style.maxHeight !== '0' &&
            mobileForm.style.maxHeight !== '0px';
        if (mobileOpen) {
            mobileForm.style.maxHeight = '0';
            mobileJoinBtn.classList.remove('ring-2', 'ring-white/30');
        }
    });
}

function initJoinPanel() {
    installEscapeHandlerOnce();

    // Desktop
    const btn = document.getElementById('join-btn');
    const panel = document.getElementById('join-panel');

    if (btn && panel && btn.dataset.joinPanelInit !== '1') {
        btn.dataset.joinPanelInit = '1';

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

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (open) {
                closePanel();
            } else {
                openPanel();
            }
        });
    }

    // Mobile (menu burger)
    const mobileBtn = document.getElementById('mobile-join-btn');
    const mobileForm = document.getElementById('mobile-join-form');

    if (mobileBtn && mobileForm && mobileBtn.dataset.joinPanelInit !== '1') {
        mobileBtn.dataset.joinPanelInit = '1';

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

        mobileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (mobileOpen) {
                closeMobile();
            } else {
                openMobile();
            }
        });
    }
}

function bootJoinPanel() {
    initJoinPanel();
    // Auto-open when redirected back after a login error (?open=login)
    if (new URLSearchParams(window.location.search).get('open') === 'login') {
        const panel = document.getElementById('join-panel');
        const btn = document.getElementById('join-btn');
        if (panel && btn) {
            panel.style.maxHeight = panel.scrollHeight + 'px';
            btn.classList.add('ring-2', 'ring-white/30');
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootJoinPanel, { once: true });
} else {
    bootJoinPanel();
}

document.addEventListener('turbo:load', initJoinPanel);
