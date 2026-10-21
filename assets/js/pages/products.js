document.addEventListener("DOMContentLoaded", () => {
    const sortSelect = document.querySelector("[data-catalog-sort]");
    const productGrid = document.querySelector("[data-catalog-grid]");

    if (sortSelect && productGrid) {
        sortSelect.addEventListener("change", () => {
            const products = Array.from(
                productGrid.querySelectorAll("[data-catalog-product]")
            );
            const sortMode = sortSelect.value;

            products.sort((first, second) => {
                if (sortMode === "price-asc") {
                    return Number(first.dataset.price) - Number(second.dataset.price);
                }
                if (sortMode === "price-desc") {
                    return Number(second.dataset.price) - Number(first.dataset.price);
                }
                if (sortMode === "name-asc") {
                    return (first.dataset.name || "").localeCompare(
                        second.dataset.name || "",
                        "vi"
                    );
                }
                return Number(first.dataset.position) - Number(second.dataset.position);
            });

            products.forEach((product) => {
                productGrid.append(product);
            });
        });

        productGrid.querySelectorAll("[data-catalog-product]").forEach((product, index) => {
            product.dataset.position = String(index);
        });
    }

    const filterToggle = document.querySelector("[data-catalog-filter-toggle]");
    const filterSidebar = document.querySelector("[data-catalog-sidebar]");

    if (filterToggle && filterSidebar) {
        filterToggle.addEventListener("click", () => {
            const isOpen = filterSidebar.classList.toggle("is-open");
            filterToggle.setAttribute("aria-expanded", String(isOpen));
        });

        filterSidebar.querySelectorAll("input[type='radio']").forEach((input) => {
            input.addEventListener("change", () => {
                if (window.matchMedia("(max-width: 767.98px)").matches) {
                    filterSidebar.scrollIntoView({ behavior: "smooth", block: "nearest" });
                }
            });
        });
    }
});
