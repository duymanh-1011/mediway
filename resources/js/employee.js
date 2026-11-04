const menu = document.querySelector('.employee-menu');
if (menu) {
    const wide = window.matchMedia('(min-width: 901px)');
    menu.open = wide.matches;
    wide.addEventListener('change', (event) => { menu.open = event.matches; });
}
