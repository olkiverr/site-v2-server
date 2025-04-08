document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'dashboard';
    setActiveTab(activeTab);

    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.getAttribute('data-tab');
            console.log(tab, tabName);
            setActiveTab(tabName);
            // Update the URL parameter without reloading the page
            history.replaceState(null, '', `?tab=${tabName}`);
        });
    });
    
    // Initialiser le compteur de résultats
    if (activeTab === 'pages') {
        const visibleItems = getVisibleItems();
        updateResultsCount(visibleItems.length);
    }
});

// Function to set the active tab
function setActiveTab(tabName) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active-tab'));
    document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active-tab'));
    document.querySelector(`.tab[data-tab="${tabName}"]`).classList.add('active-tab');
    document.getElementById(tabName).classList.add('active-tab');
}

document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-page');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const id = tab.getAttribute('data-tab');
            console.log(id);
            window.location.href = "edit_page.php?id=" + id;

        });
    });
});


function searchUsers() {
    var input, filter, table, tr, i, txtValue;
    input = document.getElementById("userSearch");
    filter = input.value.toUpperCase();
    table = document.getElementById("usersTable");
    tr = table.getElementsByTagName("tr");
    for (i = 1; i < tr.length; i++) {
        tr[i].style.display = "none";
        td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
            if (td[j]) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                    break;
                }
            }
        }
    }
}

// Fonction pour rechercher des animes par titre
function searchAnime() {
    const input = document.getElementById("pageSearch");
    const filter = input.value.toUpperCase();
    const container = document.getElementById("anime-container");
    const items = container.getElementsByClassName("tab-page");
    const genreFilter = document.getElementById("genreFilter").value;
    
    let visibleCount = 0;
    
    for (let i = 0; i < items.length; i++) {
        const title = items[i].getAttribute("data-title");
        const genres = items[i].getAttribute("data-genres");
        
        // Vérifier si le titre correspond à la recherche
        const matchesSearch = title.toUpperCase().indexOf(filter) > -1;
        
        // Vérifier si les genres correspondent au filtre
        const matchesGenre = genreFilter === "" || genres.indexOf(genreFilter) > -1;
        
        // Afficher l'élément uniquement s'il correspond à la fois à la recherche et au filtre
        if (matchesSearch && matchesGenre) {
            items[i].style.display = "";
            visibleCount++;
        } else {
            items[i].style.display = "none";
        }
    }
    
    // Mettre à jour le compteur de résultats
    updateResultsCount(visibleCount);
}

// Fonction pour filtrer les animes par genre
function filterByGenre() {
    // Utiliser la même fonction que searchAnime pour appliquer les deux filtres
    searchAnime();
}

// Fonction pour mettre à jour le compteur de résultats
function updateResultsCount(count) {
    const resultsCount = document.getElementById("results-count");
    if (resultsCount) {
        resultsCount.textContent = count + (count === 1 ? " animé found" : " animés found");
    }
}

// Gestion de la sélection multiple et de la suppression
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.select-checkbox');
    const deleteBtn = document.getElementById('delete-selection-btn');
    const deleteModal = document.getElementById('delete-confirm-modal');
    const confirmDelete = document.getElementById('confirm-delete');
    const cancelDelete = document.getElementById('cancel-delete');
    const countSelected = document.getElementById('count-selected');
    
    // Modal de confirmation pour tout sélectionner
    const selectAllModal = document.getElementById('select-all-confirm-modal');
    const confirmSelectAll = document.getElementById('confirm-select-all');
    const cancelSelectAll = document.getElementById('cancel-select-all');
    const countVisible = document.getElementById('count-visible');
    
    // Convertir les icônes carrés en checkbox
    checkboxes.forEach(checkbox => {
        // Ajouter l'icône check-square pour l'état sélectionné
        const checkIcon = document.createElement('i');
        checkIcon.className = 'fas fa-check-square';
        checkbox.appendChild(checkIcon);
        
        checkbox.addEventListener('click', function(e) {
            e.stopPropagation(); // Empêcher le click de propager au parent (tab-page)
            
            // Toggle la classe selected sur la checkbox
            this.classList.toggle('selected');
            
            // Toggle la classe selected sur le parent (tab-page)
            const tabPage = this.closest('.tab-page');
            tabPage.classList.toggle('selected');
            
            // Mettre à jour le compteur et l'affichage du bouton de suppression
            updateDeleteButton();
        });
    });
    
    // Mise à jour du bouton de suppression
    function updateDeleteButton() {
        const selectedCount = document.querySelectorAll('.select-checkbox.selected').length;
        
        if (selectedCount > 0) {
            deleteBtn.style.display = 'flex';
            deleteBtn.innerHTML = `<i class="fas fa-trash-alt"></i> Delete (${selectedCount})`;
        } else {
            deleteBtn.style.display = 'none';
        }
    }
    
    // Ouvrir la modal de confirmation
    deleteBtn.addEventListener('click', function() {
        const selectedCount = document.querySelectorAll('.select-checkbox.selected').length;
        countSelected.textContent = selectedCount;
        deleteModal.style.display = 'flex';
    });
    
    // Fermer la modal si on clique sur Annuler
    cancelDelete.addEventListener('click', function() {
        deleteModal.style.display = 'none';
    });
    
    // Fermer la modal si on clique en dehors
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.style.display = 'none';
        }
    });
    
    // Confirmer la suppression
    confirmDelete.addEventListener('click', function() {
        const selectedIds = [];
        document.querySelectorAll('.select-checkbox.selected').forEach(checkbox => {
            selectedIds.push(checkbox.getAttribute('data-id'));
        });
        
        // Appel AJAX pour supprimer les pages sélectionnées
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../php/delete_multiple_pages.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Suppression réussie, recharger la page
                window.location.reload();
            } else {
                // Erreur
                alert('Une erreur est survenue lors de la suppression.');
                deleteModal.style.display = 'none';
            }
        };
        xhr.send('ids=' + JSON.stringify(selectedIds));
    });
    
    // Empêcher la propagation du clic sur tab-page vers le parent
    document.querySelectorAll('.tab-page').forEach(tab => {
        tab.addEventListener('click', function(e) {
            // Si on a cliqué sur la checkbox, ne pas naviguer vers edit_page.php
            if (e.target.closest('.select-checkbox') || e.target.classList.contains('select-checkbox')) {
                return;
            }
            
            const id = this.getAttribute('data-tab');
            window.location.href = "edit_page.php?id=" + id;
        });
    });
    
    // Modal de confirmation pour tout sélectionner
    if (cancelSelectAll) {
        cancelSelectAll.addEventListener('click', function() {
            selectAllModal.style.display = 'none';
        });
    }
    
    // Fermer la modal si on clique en dehors
    if (selectAllModal) {
        selectAllModal.addEventListener('click', function(e) {
            if (e.target === selectAllModal) {
                selectAllModal.style.display = 'none';
            }
        });
    }
    
    // Confirmer la sélection de tous les éléments visibles
    if (confirmSelectAll) {
        confirmSelectAll.addEventListener('click', function() {
            selectAllVisibleItems();
            selectAllModal.style.display = 'none';
        });
    }
});

// Fonction pour afficher la confirmation de sélection de tous les éléments
function showSelectAllConfirmation() {
    const visibleItems = getVisibleItems();
    const countVisible = document.getElementById('count-visible');
    const selectAllModal = document.getElementById('select-all-confirm-modal');
    
    // Mettre à jour le nombre d'éléments visibles
    countVisible.textContent = visibleItems.length;
    
    // Afficher la modal de confirmation
    selectAllModal.style.display = 'flex';
}

// Fonction pour obtenir tous les éléments actuellement visibles
function getVisibleItems() {
    const container = document.getElementById('anime-container');
    const items = container.getElementsByClassName('tab-page');
    const visibleItems = [];
    
    for (let i = 0; i < items.length; i++) {
        if (items[i].style.display !== 'none') {
            visibleItems.push(items[i]);
        }
    }
    
    return visibleItems;
}

// Fonction pour sélectionner tous les éléments actuellement visibles
function selectAllVisibleItems() {
    const visibleItems = getVisibleItems();
    
    // Sélectionner chaque élément visible
    visibleItems.forEach(item => {
        const checkbox = item.querySelector('.select-checkbox');
        if (checkbox && !checkbox.classList.contains('selected')) {
            // Ajouter la classe selected à la checkbox
            checkbox.classList.add('selected');
            
            // Ajouter la classe selected au parent (tab-page)
            item.classList.add('selected');
        }
    });
    
    // Mettre à jour le bouton de suppression
    updateDeleteButtonAfterSelectAll();
}

// Mise à jour du bouton de suppression après une sélection multiple
function updateDeleteButtonAfterSelectAll() {
    const selectedCount = document.querySelectorAll('.select-checkbox.selected').length;
    const deleteBtn = document.getElementById('delete-selection-btn');
    
    if (selectedCount > 0) {
        deleteBtn.style.display = 'flex';
        deleteBtn.innerHTML = `<i class="fas fa-trash-alt"></i> Delete (${selectedCount})`;
    } else {
        deleteBtn.style.display = 'none';
    }
}
