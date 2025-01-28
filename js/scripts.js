const slider = document.querySelector('.trending-slider');
const leftButton = document.querySelector('.slider-button.left');
const rightButton = document.querySelector('.slider-button.right');

leftButton.addEventListener('click', function() {
    slider.scrollBy({
        left: -200,
        behavior: 'smooth'
    });
});

rightButton.addEventListener('click', function() {
    slider.scrollBy({
        left: 200,
        behavior: 'smooth'
    });
});

const upcomingSlider = document.querySelector('.upcoming-slider');
const upcomingLeftButton = document.querySelector('.upcoming-slider-container .slider-button.left');
const upcomingRightButton = document.querySelector('.upcoming-slider-container .slider-button.right');

upcomingLeftButton.addEventListener('click', function() {
    upcomingSlider.scrollBy({
        left: -200,
        behavior: 'smooth'
    });
});

upcomingRightButton.addEventListener('click', function() {
    upcomingSlider.scrollBy({
        left: 200,
        behavior: 'smooth'
    });
});

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