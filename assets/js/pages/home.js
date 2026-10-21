document.addEventListener("DOMContentLoaded", () => {
    document.documentElement.classList.add("home-js-enabled");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const updateCarouselStates = [];

    document.querySelectorAll("[data-home-carousel]").forEach((viewport) => {
        const carouselName = viewport.dataset.homeCarousel;
        const controls = document.querySelector(`[data-home-carousel-controls="${carouselName}"]`);
        const previousButton = document.querySelector(`[data-home-carousel-prev="${carouselName}"]`);
        const nextButton = document.querySelector(`[data-home-carousel-next="${carouselName}"]`);

        if (!controls || !previousButton || !nextButton) {
            return;
        }

        const updateState = () => {
            const maxScroll = viewport.scrollWidth - viewport.clientWidth;
            controls.hidden = maxScroll <= 1;
            previousButton.disabled = viewport.scrollLeft <= 1;
            nextButton.disabled = viewport.scrollLeft >= maxScroll - 1;
        };

        previousButton.addEventListener("click", () => {
            viewport.scrollBy({
                left: -viewport.clientWidth,
                behavior: reducedMotion ? "auto" : "smooth"
            });
        });

        nextButton.addEventListener("click", () => {
            viewport.scrollBy({
                left: viewport.clientWidth,
                behavior: reducedMotion ? "auto" : "smooth"
            });
        });

        viewport.addEventListener("scroll", updateState, { passive: true });
        updateCarouselStates.push(updateState);
        updateState();
    });

    window.addEventListener("resize", () => {
        updateCarouselStates.forEach((updateState) => updateState());
    });

    const revealItems = document.querySelectorAll("[data-home-reveal]");

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
        { threshold: 0.12, rootMargin: "0px 0px -24px 0px" }
    );

    revealItems.forEach((item) => observer.observe(item));
});
