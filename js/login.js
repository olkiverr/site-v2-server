document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');

    loginForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        
        fetch('/4TTJ/Zielinski%20Olivier/Site/site-v2/php/auth.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text()) // Change here to get response as plain text
        .then(text => {
            console.log("Raw text response:", text); // Display raw text response
            try {
                const data = JSON.parse(text); // Try to convert response to JSON
                console.log(data); // Affiche l'objet JSON
                if (data.status === 'success') {
                    showSnackbar(data.message, 'success');
                    localStorage.setItem('bearer_token', data.token);
                    setTimeout(() => {
                        window.location.href = '/4TTJ/Zielinski%20Olivier/Site/site-v2/index.php';
                    }, 1500);
                } else {
                    showSnackbar(data.message, 'error');
                }
            } catch (error) {
                console.error("Error parsing JSON:", error); // Affiche l'erreur si la conversion JSON échoue
                showSnackbar('An error occurred. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error); // Gère l'erreur dans le cas d'un échec de la requête
            showSnackbar('An error occurred. Please try again.', 'error');
        });
    });
});
