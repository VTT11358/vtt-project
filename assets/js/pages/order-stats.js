document.addEventListener("DOMContentLoaded", () => {
    const chartYear = document.querySelector("[data-submit-form]");
    if (chartYear) {
        chartYear.addEventListener("change", () => {
            const form = document.getElementById(chartYear.getAttribute("form"));
            if (form instanceof HTMLFormElement) {
                form.requestSubmit();
            }
        });
    }

    const chartFilter = document.querySelector("[data-chart-filter]");
    if (!chartFilter) {
        return;
    }

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
});
