const THEME_KEY = 'wealthflow.theme';

export function isDarkMode() {
  return document.documentElement.dataset.theme === 'dark';
}

export function syncThemeSwitch() {
  const toggle = document.querySelector('[data-theme-toggle]');
  if (!toggle) return;
  toggle.setAttribute('aria-checked', String(isDarkMode()));
  toggle.setAttribute('aria-label', 'Chế độ tối');
}

export function toggleTheme() {
  const nextTheme = isDarkMode() ? 'light' : 'dark';
  document.documentElement.dataset.theme = nextTheme;
  document.documentElement.style.colorScheme = nextTheme;
  try {
    localStorage.setItem(THEME_KEY, nextTheme);
    syncThemeSwitch();
    return { theme: nextTheme, saved: true };
  } catch (error) {
    console.warn('Could not save the theme preference.', error);
    syncThemeSwitch();
    return { theme: nextTheme, saved: false };
  }
}

window.addEventListener('storage', event => {
  if (event.key !== THEME_KEY) return;
  const theme = event.newValue === 'dark' ? 'dark' : 'light';
  document.documentElement.dataset.theme = theme;
  document.documentElement.style.colorScheme = theme;
  syncThemeSwitch();
});
