document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const mobileThemeToggle = document.getElementById('mobile-theme-toggle');
    const html = document.documentElement;
    const body = document.body;

    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');

    // Apply saved theme or system preference
    if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        html.classList.add('light-mode');
        body.classList.add('light-mode');
        updateToggleIcons(true);
    }

    function toggleTheme() {
        const isLight = html.classList.toggle('light-mode');
        body.classList.toggle('light-mode');
        localStorage.setItem('theme', isLight ? 'light' : 'dark');
        updateToggleIcons(isLight);
    }

    function updateToggleIcons(isLight) {
        const toggles = [themeToggle, mobileThemeToggle];
        toggles.forEach(toggle => {
            if (!toggle) return;
            const sunIcon = toggle.querySelector('.sun-icon');
            const moonIcon = toggle.querySelector('.moon-icon');

            if (isLight) {
                sunIcon?.classList.add('hidden');
                moonIcon?.classList.remove('hidden');
            } else {
                sunIcon?.classList.remove('hidden');
                moonIcon?.classList.add('hidden');
            }
        });
    }

    if (themeToggle) themeToggle.addEventListener('click', toggleTheme);
    if (mobileThemeToggle) mobileThemeToggle.addEventListener('click', toggleTheme);
});
