(() => {
    const hamburger = document.getElementById('navHamburger');
    const navActions = document.getElementById('navActions');
  
    if (!hamburger || !navActions) return;

    const toggleMenu = () => {
        const isOpen = navActions.classList.toggle('is-open');
        hamburger.classList.toggle('is-active', isOpen);
        hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    hamburger.addEventListener('click', toggleMenu);

    navActions.querySelectorAll('.nav-link, .btn').forEach((link) => {
        link.addEventListener('click', () => {
        navActions.classList.remove('is-open');
        hamburger.classList.remove('is-active');
        hamburger.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !navActions.contains(e.target)) {
            navActions.classList.remove('is-open');
            hamburger.classList.remove('is-active');
            hamburger.setAttribute('aria-expanded', 'false');
        }
    });
})();