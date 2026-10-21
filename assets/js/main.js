document.addEventListener("DOMContentLoaded", () => {
    const adminMenuToggle = document.querySelector("[data-admin-menu-toggle]");
    const adminShell = document.querySelector(".admin-shell");

    if (adminMenuToggle && adminShell) {
        adminMenuToggle.addEventListener("click", () => {
            const isOpen = adminShell.classList.toggle("sidebar-open");
            adminMenuToggle.setAttribute("aria-expanded", String(isOpen));
            adminMenuToggle.setAttribute(
                "aria-label",
                isOpen ? "Đóng menu quản trị" : "Mở menu quản trị"
            );
        });

        adminShell.addEventListener("click", (event) => {
            if (event.target === adminShell && adminShell.classList.contains("sidebar-open")) {
                adminShell.classList.remove("sidebar-open");
                adminMenuToggle.setAttribute("aria-expanded", "false");
                adminMenuToggle.setAttribute("aria-label", "Mở menu quản trị");
            }
        });
    }

    const menuToggle = document.querySelector(".menu-toggle");
    const mainMenu = document.querySelector("#mainMenu");

    if (menuToggle && mainMenu) {
        menuToggle.addEventListener("click", () => {
            const isOpen = menuToggle.getAttribute("aria-expanded") === "true";
            menuToggle.setAttribute("aria-expanded", String(!isOpen));
            menuToggle.setAttribute("aria-label", isOpen ? "Mở menu" : "Đóng menu");
            mainMenu.classList.toggle("is-open", !isOpen);
        });
    }

    const accountMenu = document.querySelector("[data-account-menu]");
    const accountTrigger = accountMenu?.querySelector("summary");

    if (accountMenu && accountTrigger) {
        accountMenu.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                accountMenu.open = false;
                accountTrigger.focus();
            }
        });

        document.addEventListener("click", (event) => {
            if (event.target instanceof Node && !accountMenu.contains(event.target)) {
                accountMenu.open = false;
            }
        });
    }

    document.querySelectorAll("[data-account-avatar-image]").forEach((image) => {
        image.addEventListener("error", () => {
            const defaultAvatar = image.getAttribute("data-default-avatar");
            if (defaultAvatar && image.getAttribute("src") !== defaultAvatar) {
                image.setAttribute("src", defaultAvatar);
            }
        }, { once: true });
    });

    const chartYear = document.querySelector("[data-submit-form]");
    if (chartYear) {
        chartYear.addEventListener("change", () => {
            const form = document.getElementById(chartYear.getAttribute("form"));
            if (form instanceof HTMLFormElement) {
                form.requestSubmit();
            }
        });
    }

    const checkoutSubmit = document.querySelector("[data-checkout-submit]");
    const checkoutSubmitLabel = checkoutSubmit?.querySelector("[data-checkout-submit-label]");
    if (checkoutSubmit && checkoutSubmitLabel) {
        document.querySelectorAll('input[name="payment_method"]').forEach((input) => {
            input.addEventListener("change", () => {
                checkoutSubmitLabel.textContent = input.value === "vnpay"
                    ? "Thanh toán qua VNPay"
                    : "Đặt hàng";
            });
        });
    }

    const chartFilter = document.querySelector("[data-chart-filter]");
    if (chartFilter) {
        const chartColumns = document.querySelectorAll(".stats-chart-column[data-month]");

        chartFilter.addEventListener("change", () => {
            const [filterType, filterValue] = chartFilter.value.split("-");
            const selectedValue = Number.parseInt(filterValue, 10);

            chartColumns.forEach((column) => {
                const month = Number.parseInt(column.getAttribute("data-month"), 10);
                let isSelected = true;

                if (filterType === "month") {
                    isSelected = month === selectedValue;
                } else if (filterType === "quarter") {
                    const firstMonth = (selectedValue - 1) * 3 + 1;
                    isSelected = month >= firstMonth && month < firstMonth + 3;
                }

                column.classList.toggle("is-dimmed", !isSelected);
            });
        });
    }

    document.addEventListener("click", (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const closeButton = event.target.closest("[data-dismiss-alert]");
        const alert = closeButton?.closest(".alert");

        if (alert) {
            alert.remove();
        }
    });
});
