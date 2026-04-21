function see_params(value) {
    const block = document.getElementById("transfer_params");
    if (value == 1) {
        block.classList.remove("d-none");
    } else {
        block.classList.add("d-none");
    }
}

function see_category(value) {
    const block = document.getElementById("category_block");
    if (value == 0) {
        block.classList.remove("d-none");
    } else {
        block.classList.add("d-none");
    }
}
