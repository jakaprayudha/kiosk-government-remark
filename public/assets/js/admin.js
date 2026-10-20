const menuButton = document.querySelector('.menu-button');
const sidebar = document.querySelector('#admin-sidebar');

function closeAdminMenu() {
    sidebar?.classList.remove('is-open');
    menuButton?.setAttribute('aria-expanded', 'false');
}

menuButton?.addEventListener('click', () => {
    const expanded = menuButton.getAttribute('aria-expanded') === 'true';
    sidebar?.classList.toggle('is-open', !expanded);
    menuButton.setAttribute('aria-expanded', String(!expanded));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menuButton?.getAttribute('aria-expanded') === 'true') {
        closeAdminMenu();
        menuButton.focus();
    }
});

document.addEventListener('click', (event) => {
    if (event.target instanceof Node && !sidebar?.contains(event.target) && !menuButton?.contains(event.target)) {
        closeAdminMenu();
    }
});
