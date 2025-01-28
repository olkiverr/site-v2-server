document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const nav = document.getElementById('main-nav');
    const navMenu = document.getElementById('nav-menu');
    const searchBar = document.querySelector('.responsive-search-bar');
    const userIcon = document.getElementById('responsive-user-icon');

    menuToggle.addEventListener('click', function() {
        nav.classList.toggle('active-header');
        navMenu.classList.toggle('active-header');
        searchBar.classList.toggle('active-header');
        userIcon.classList.toggle('active-header');
    });
});

function toggleUserMenu(menuId) {
    var userMenu = document.getElementById(menuId);
    if (userMenu.style.display === 'none' || userMenu.style.display === '') {
        userMenu.style.display = 'block';
    } else {
        userMenu.style.display = 'none';
    }
}
    