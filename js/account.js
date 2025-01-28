const save_button = document.getElementById("save-info");
const edit_button = document.getElementById("edit-info");
const name_input = document.getElementById("name-input");
const email_input = document.getElementById("email-input");

function toggleEdit() {
    save_button.style.display = "block";
    edit_button.style.display = "none";
    name_input.removeAttribute("disabled");
    email_input.removeAttribute("disabled");
}

function save() {
    save_button.style.display = "none";
    edit_button.style.display = "block";
    name_input.setAttribute("disabled", true);
    email_input.setAttribute("disabled", true)
}