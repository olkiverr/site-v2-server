document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');

    loginForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch('/4TTJ/Zielinski%20Olivier/Site/site-v2/php/auth.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSnackbar(data.message, 'success');
                setTimeout(() => {
                    window.location.href = '/4TTJ/Zielinski%20Olivier/Site/site-v2/index.php';
                }, 1500);
            } else {
                showSnackbar(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showSnackbar('An error occurred. Please try again.', 'error');
        });
    });
});
