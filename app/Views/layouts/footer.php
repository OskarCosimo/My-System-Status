<!-- path: app/Views/layouts/footer.php -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar toggle and responsive behavior
    const sidebar = document.getElementById('sidebar-wrapper');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (sidebar && toggleBtn) {
        const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true' || window.innerWidth < 992;
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }

        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
        });

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.innerWidth < 992) {
                    sidebar.classList.add('collapsed');
                } else if (localStorage.getItem('sidebar_collapsed') !== 'true') {
                    sidebar.classList.remove('collapsed');
                }
            }, 100);
        });
    }

    // 2. Theme switcher interactive logic (Light / Dark / Auto)
    const getStoredTheme = () => localStorage.getItem('theme') || 'auto';
    const setStoredTheme = (theme) => localStorage.setItem('theme', theme);

    const themeIcons = {
        light: 'bi-sun-fill',
        dark: 'bi-moon-stars-fill',
        auto: 'bi-circle-half'
    };

    const applyTheme = (theme) => {
        if (theme === 'auto') {
            const systemScheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', systemScheme);
        } else {
            document.documentElement.setAttribute('data-bs-theme', theme);
        }
    };

    const updateThemeUi = (theme) => {
        document.querySelectorAll('.theme-switcher').forEach((container) => {
            const activeIcon = container.querySelector('.theme-icon-active');
            if (activeIcon) {
                activeIcon.className = `bi ${themeIcons[theme] || 'bi-circle-half'} theme-icon-active`;
            }

            container.querySelectorAll('[data-bs-theme-value]').forEach((btn) => {
                const isSelected = btn.getAttribute('data-bs-theme-value') === theme;
                btn.classList.toggle('active', isSelected);
                btn.classList.toggle('fw-bold', isSelected);

                const checkMark = btn.querySelector('.check-mark');
                if (checkMark) {
                    checkMark.classList.toggle('d-none', !isSelected);
                }
            });
        });
    };

    const currentTheme = getStoredTheme();
    applyTheme(currentTheme);
    updateThemeUi(currentTheme);

    // Listen for manual switch clicks
    document.querySelectorAll('[data-bs-theme-value]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const selectedTheme = toggle.getAttribute('data-bs-theme-value');
            setStoredTheme(selectedTheme);
            applyTheme(selectedTheme);
            updateThemeUi(selectedTheme);
        });
    });

    // Listen for OS system theme changes when 'auto' mode is active
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getStoredTheme() === 'auto') {
            applyTheme('auto');
        }
    });
});
</script>
