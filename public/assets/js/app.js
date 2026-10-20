const menuToggle = document.querySelector('.menu-toggle');
const siteNav = document.querySelector('#site-nav');

menuToggle?.addEventListener('click', () => {
    const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
    menuToggle.setAttribute('aria-expanded', String(!isExpanded));
    siteNav?.classList.toggle('is-open', !isExpanded);
});

siteNav?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        menuToggle?.setAttribute('aria-expanded', 'false');
        siteNav.classList.remove('is-open');
    });
});

const searchInput = document.querySelector('#service-search');
const serviceCards = [...document.querySelectorAll('.service-card')];
const noResults = document.querySelector('#no-results');

searchInput?.addEventListener('input', () => {
    const query = searchInput.value.trim().toLocaleLowerCase('id');
    let visibleCards = 0;

    serviceCards.forEach((card) => {
        const matches = card.textContent.toLocaleLowerCase('id').includes(query)
            || card.dataset.search.includes(query);
        card.hidden = !matches;
        if (matches) visibleCards += 1;
    });

    if (noResults) noResults.hidden = visibleCards > 0;
});

document.addEventListener('keydown', (event) => {
    if (event.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
        event.preventDefault();
        searchInput?.focus();
    }
});

const currentYear = document.querySelector('#current-year');
if (currentYear) currentYear.textContent = String(new Date().getFullYear());
