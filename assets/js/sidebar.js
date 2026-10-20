(() => {
    const toggle = document.querySelector('.menu-toggle');
    const menu = document.getElementById('sidebar-menu');
    if (!toggle || !menu) return;
    const mobile = window.matchMedia('(max-width: 800px)');
    const setOpen = (open) => {
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };
    const adapt = () => {
        toggle.hidden = !mobile.matches;
        setOpen(!mobile.matches);
    };
    toggle.addEventListener('click', () => setOpen(menu.hidden));
    menu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobile.matches) {
            setOpen(false);
            toggle.focus();
        }
    });
    mobile.addEventListener('change', adapt);
    adapt();
})();
