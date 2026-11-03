(() => {
  try {
    document.documentElement.dataset.theme =
      localStorage.getItem('wealthflow.theme') === 'dark' ? 'dark' : 'light';
  } catch (error) {
    console.warn('Could not read the saved theme preference.', error);
    document.documentElement.dataset.theme = 'light';
  }
})();
