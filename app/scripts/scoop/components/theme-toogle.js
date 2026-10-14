export default ($) => {
  const root = document.documentElement;
  const preference = window.matchMedia('(prefers-color-scheme: dark)');
  const isDark = () => root.dataset.theme === 'dark'
    || (root.dataset.theme !== 'light' && preference.matches);
  const update = () => $.label = isDark() ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
  let observer;
  const storageChanged = event => {
    if (event.key !== 'scoop.theme' && event.key !== null) return;
    if (event.newValue === 'light' || event.newValue === 'dark') root.dataset.theme = event.newValue;
    else delete root.dataset.theme;
    update();
  };

  return {
    mount: () => {
      preference.addEventListener('change', update);
      window.addEventListener('storage', storageChanged);
      observer = new MutationObserver(update);
      observer.observe(root, { attributes: true, attributeFilter: ['data-theme'] });
      update();
    },
    click: () => {
      const theme = isDark() ? 'light' : 'dark';
      root.dataset.theme = theme;
      try { localStorage.setItem('scoop.theme', theme); } catch (error) { }
      update();
    },
    unmount: () => {
      preference.removeEventListener('change', update);
      window.removeEventListener('storage', storageChanged);
      observer?.disconnect();
    }
  };
};
