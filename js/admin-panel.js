document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'dashboard';
    setActiveTab(activeTab);

    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.getAttribute('data-tab');
            setActiveTab(tabName);
            history.replaceState(null, '', `?tab=${tabName}`);
        });
    });
});

function setActiveTab(tabName) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active-tab'));
    document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active-tab'));
    document.querySelector(`.tab[data-tab="${tabName}"]`).classList.add('active-tab');
    document.getElementById(tabName).classList.add('active-tab');
}

function searchUsers() {
    var input, filter, table, tr, td, i, txtValue;
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
