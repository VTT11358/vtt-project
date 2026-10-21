document.addEventListener("DOMContentLoaded", () => {
    document.documentElement.classList.add("journal-js-enabled");
    const revealItems = document.querySelectorAll("[data-journal-reveal]");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (!("IntersectionObserver" in window) || reducedMotion) {
        revealItems.forEach((item) => item.classList.add("is-visible"));
        return;
    }

    const observer = new IntersectionObserver(
        (entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    currentObserver.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: "0px 0px -20px 0px" }
    );

    revealItems.forEach((item) => observer.observe(item));
});
