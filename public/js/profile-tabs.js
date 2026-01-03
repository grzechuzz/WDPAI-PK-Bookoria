(() => {
  const buttons = document.querySelectorAll('.tab-btn');
  const panels = document.querySelectorAll('[data-tab-panel]');

  if (!buttons.length || !panels.length) return;

  const setActive = (tab) => {
    panels.forEach((p) => {
      p.hidden = p.getAttribute('data-tab-panel') !== tab;
    });

    buttons.forEach((b) => {
      const isActive = b.getAttribute('data-tab') === tab;
      b.classList.toggle('is-active', isActive);
      b.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    try { localStorage.setItem('bookoria_profile_tab', tab); } catch (e) {}
  };

  const firstTab = buttons[0].getAttribute('data-tab') || 'loans';

  let initial = firstTab;
  try { initial = localStorage.getItem('bookoria_profile_tab') || firstTab; } catch (e) {}

  setActive(initial);

  buttons.forEach((b) => {
    b.addEventListener('click', () => {
      const tab = b.getAttribute('data-tab') || firstTab;
      setActive(tab);
    });
  });
})();
