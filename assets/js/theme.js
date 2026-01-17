document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;

  // ==============================
  // Tema (claro/escuro)
  // ==============================
  const themeToggleButton = document.getElementById('theme-toggle');

  const applyTheme = (theme) => {
    if (theme === 'light') body.classList.add('light');
    else body.classList.remove('light');
  };

  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) applyTheme(savedTheme);

  if (themeToggleButton) {
    themeToggleButton.addEventListener('click', () => {
      const isLight = body.classList.contains('light');
      const newTheme = isLight ? 'dark' : 'light';
      applyTheme(newTheme);
      localStorage.setItem('theme', newTheme);
    });
  }

  // ==============================
  // Tela cheia (fullscreen)
  // ==============================
  const fsBtn = document.getElementById('fullscreen-toggle');

  const isFullscreen = () =>
    !!(
      document.fullscreenElement ||
      document.webkitFullscreenElement ||
      document.mozFullScreenElement ||
      document.msFullscreenElement
    );

  const updateFsIcon = () => {
    if (!fsBtn) return;
    const on = isFullscreen();
    fsBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
    fsBtn.setAttribute('title', on ? 'Sair da tela cheia' : 'Tela cheia');
    fsBtn.textContent = on ? '🗗' : '⛶';
  };

  const requestFs = (el) => {
    const fn =
      el.requestFullscreen ||
      el.webkitRequestFullscreen ||
      el.mozRequestFullScreen ||
      el.msRequestFullscreen;
    if (fn) fn.call(el);
  };

  const exitFs = () => {
    const fn =
      document.exitFullscreen ||
      document.webkitExitFullscreen ||
      document.mozCancelFullScreen ||
      document.msExitFullscreen;
    if (fn) fn.call(document);
  };

  if (fsBtn) {
    updateFsIcon();

    fsBtn.addEventListener('click', () => {
      if (isFullscreen()) exitFs();
      else requestFs(document.documentElement);
    });

    document.addEventListener('fullscreenchange', updateFsIcon);
    document.addEventListener('webkitfullscreenchange', updateFsIcon);
    document.addEventListener('mozfullscreenchange', updateFsIcon);
    document.addEventListener('MSFullscreenChange', updateFsIcon);
  }
});
