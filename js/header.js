document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const nav = document.getElementById('main-nav');
    const navMenu = document.getElementById('nav-menu');
    const searchBar = document.querySelector('.responsive-search-bar');
    const userIcon = document.getElementById('responsive-user-icon');
    const searchInput = document.querySelector('.search-input');
    
    // Create results container
    const resultsContainer = document.createElement('div');
    resultsContainer.className = 'search-results';
    searchInput.parentNode.appendChild(resultsContainer);

    menuToggle.addEventListener('click', function() {
        nav.classList.toggle('active-header');
        navMenu.classList.toggle('active-header');
        searchBar.classList.toggle('active-header');
        userIcon.classList.toggle('active-header');
    });

    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length === 0) {
            resultsContainer.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`/4TTJ/Zielinski%20Olivier/Site/site-v2/php/search.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(result => {
                            const resultItem = document.createElement('div');
                            resultItem.className = 'search-result-item';
                            resultItem.innerHTML = `
                                <img src="${result.img}" alt="${result.title}">
                                <span>${result.title}</span>
                            `;
                            resultItem.addEventListener('click', () => {
                                window.location.href = `/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/view_anime.php?id=${result.id}`;
                            });
                            resultsContainer.appendChild(resultItem);
                        });
                        resultsContainer.style.display = 'block';
                    } else {
                        resultsContainer.innerHTML = '<div class="no-results">No results found</div>';
                        resultsContainer.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }, 300); // Delay for performance
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
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
    