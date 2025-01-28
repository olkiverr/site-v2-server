function showSnackbar(message, type = 'info') {
    const snackbar = document.createElement('div');
    snackbar.className = `snackbar ${type}`;
    snackbar.textContent = message;

    document.body.appendChild(snackbar);

    setTimeout(() => {
        snackbar.classList.add('show');
    }, 100);

    setTimeout(() => {
        snackbar.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(snackbar);
        }, 300);
    }, 3000);
}
