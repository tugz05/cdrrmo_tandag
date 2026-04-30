
export const switchBootstrapTheme = (buttonId = 'btn-switch-theme', iconId = 'theme-icon') => {
    const btnSwitchTheme = document.getElementById(buttonId);
    const getCurrentThemeFromLocalStorage = localStorage.getItem('theme') || 'light';

    const switchTheme = (theme) => {
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        theme === 'dark'
            ? document.getElementById(iconId)?.setAttribute('class', 'me-1 bi bi-moon')
            : document.getElementById(iconId)?.setAttribute('class', 'me-1 bi bi-sun')
    }

    switchTheme(getCurrentThemeFromLocalStorage)

    btnSwitchTheme?.addEventListener('click', () => {
        const getCurrentThemeFromAttribute = document.documentElement.getAttribute('data-bs-theme');
        console.log(getCurrentThemeFromAttribute)
        const newTheme = getCurrentThemeFromAttribute === 'dark' ? 'light' : 'dark';
        switchTheme(newTheme)
    });
}
