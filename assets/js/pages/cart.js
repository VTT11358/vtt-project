document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".cart-alert").forEach((alert) => {
        window.setTimeout(() => {
            alert.classList.add("is-dismissing");
            window.setTimeout(() => alert.remove(), 350);
        }, 4500);
    });

    const selectAll = document.querySelector("[data-cart-select-all]");
    const selections = Array.from(document.querySelectorAll("[data-cart-select]"));
    const selectedCount = document.querySelector("[data-cart-selected-count]");
    const selectedTotal = document.querySelector("[data-cart-selected-total]");
    const checkoutButton = document.querySelector("[data-cart-checkout-button]");

    if (!selectAll || !selectedCount || !selectedTotal || !checkoutButton) {
        return;
    }

    const updateSelectionSummary = () => {
        const selectableItems = selections.filter((selection) => !selection.disabled);
        const selectedItems = selections.filter((selection) => selection.checked && !selection.disabled);
        const total = selectedItems.reduce((sum, selection) => {
            const cartItem = selection.closest("[data-cart-item]");
            return sum + Number(cartItem ? cartItem.dataset.lineTotal : 0);
        }, 0);

        selectedCount.textContent = String(selectedItems.length);
        selectedTotal.textContent = `${new Intl.NumberFormat("vi-VN").format(total)}${selectedTotal.dataset.currencySuffix || " đ"}`;
        checkoutButton.disabled = selectedItems.length === 0;
        selectAll.checked = selectableItems.length > 0
            && selectedItems.length === selectableItems.length;
        selectAll.indeterminate = selectedItems.length > 0
            && selectedItems.length < selectableItems.length;
    };

    selectAll.addEventListener("change", () => {
        selections.forEach((selection) => {
            if (!selection.disabled) {
                selection.checked = selectAll.checked;
            }
        });
        updateSelectionSummary();
    });

    selections.forEach((selection) => {
        selection.addEventListener("change", updateSelectionSummary);
    });

    updateSelectionSummary();
});
