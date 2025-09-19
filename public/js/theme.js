// Function to get the system prefered language between light and dark using media query
function getSystemPreferredTheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    } else {
        return 'light';
    }
}

// Function to set the theme by changing the data-theme attribute on <html>
function setTheme(theme) {
    const htmlEl = document.documentElement;
    if (theme === 'light' || theme === 'dark') {
        htmlEl.setAttribute('data-theme', theme);
    } else {
        // system
        const systemTheme = getSystemPreferredTheme();
        htmlEl.setAttribute('data-theme', systemTheme);
    }

    // Set localstorage
    if (STORAGE.IsAccepted()) {
        STORAGE.Set("setting.theme", theme);
    }
}

// At first check localstorage
if (STORAGE.IsAccepted() && STORAGE.Has("setting.theme")) {
    const storedTheme = STORAGE.Get("setting.theme");
    setTheme(storedTheme);
}

// Add listener for system theme changes, where we check if we are on system and in that case updates
window.matchMedia('(prefers-color-scheme: dark)').onchange = (e) => {
    const newSystemTheme = e.matches ? 'dark' : 'light';
    if (themeToggle.value === 'system') {
        setTheme(newSystemTheme);
    }
};

window.addEventListener('load', () => {
    // Add handlers for theme management
    const themeToggle = document.getElementById('theme'); // Element of type select with option values "light", "dark" and "system"
    if (themeToggle) {
        // Set initial
        const metaEntries = getPHPMetaEntries();
        let initialTheme = 'system';
        if (metaEntries.theme) {
            initialTheme = metaEntries.theme;
        }

        if (STORAGE.IsAccepted() && STORAGE.Has("setting.theme")) {
            initialTheme = STORAGE.Get("setting.theme");
        }

        themeToggle.value = initialTheme;

        setTheme(initialTheme);

        // Onchange
        themeToggle.onchange = (e) => {
            const selectedTheme = themeToggle.value;
            setTheme(selectedTheme);
        };
    }
});