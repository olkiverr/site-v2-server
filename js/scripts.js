const isAdmin = document.body.classList.contains('admin');
if (isAdmin) {
    document.querySelectorAll('.trending-item, .upcoming-item').forEach(item => {
        item.addEventListener('click', () => {
            const trendingEditMenu = document.getElementById('trending-edit-menu');
            const upcomingEditMenu = document.getElementById('upcoming-edit-menu');
            if (trendingEditMenu.style.display === 'block' || upcomingEditMenu.style.display === 'block') {
                document.querySelectorAll('.trending-item, .upcoming-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
            }
        });
    });
}

function toggleEditMenu(sliderType, cogElement=null) {
    const editMenu = document.getElementById(`${sliderType}-edit-menu`);
    editMenu.style.display = editMenu.style.display === 'block' ? 'none' : 'block';
}

function addImage(category) {
    let url, isImageUrl, title;

    if (category === 'trending') {
        url = document.getElementById('trending-image').value;
        if (url.startsWith("anime_img/")) {
            isImageUrl = true;
        } else {
            isImageUrl = url.match(/\.(jpg|jpeg|png|gif|bmp)$/i) !== null;
        }
        title = document.getElementById('trending-title').value;
    } else if (category === 'upcoming') {
        url = document.getElementById('upcoming-image').value;
        if (url.startsWith("anime_img/")) {
            isImageUrl = true;
        } else {
            isImageUrl = url.match(/\.(jpg|jpeg|png|gif|bmp)$/i) !== null;
        }
        title = document.getElementById('upcoming-title').value;
    }
    if (isImageUrl) {
        $.ajax({
            type: 'POST',
            url: 'php/add_image.php',
            data: {functionname: 'addImage', category: category, url: url, title: title}
        })
    } else {
        console.log('Invalid image URL');
    }
}

function deleteImage(category) {
    let selectedImage;

    if (category === 'trending') {
        selectedImage = document.querySelector('.trending-item.selected');
        
    } else if (category === 'upcoming') {
        selectedImage = document.querySelector('.upcoming-item.selected');
    }

    const imageId = selectedImage.getAttribute('data-id');
    
    $.ajax({
        type: 'POST',
        url: 'php/delete_image.php',
        data: {functionname: 'deleteImage', category: category, imageId: imageId}
    })
}

document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour gérer le défilement des sliders
    function setupSliders() {
        // Pour le slider des tendances
        setupSlider('trending');
        
        // Pour le slider des à venir
        setupSlider('upcoming');
    }
    
    // Configure un slider spécifique
    function setupSlider(category) {
        const slider = document.querySelector(`.${category}-slider`);
        const leftButton = slider.querySelector('.slider-button.left');
        const rightButton = slider.querySelector('.slider-button.right');
        
        // Gestionnaire pour le bouton gauche
        leftButton.addEventListener('click', function(e) {
            e.preventDefault();
            slider.scrollBy({
                left: -300,
                behavior: 'smooth'
            });
        });
        
        // Gestionnaire pour le bouton droit
        rightButton.addEventListener('click', function(e) {
            e.preventDefault();
            slider.scrollBy({
                left: 300,
                behavior: 'smooth'
            });
        });
    }
    
    setupSliders();
});