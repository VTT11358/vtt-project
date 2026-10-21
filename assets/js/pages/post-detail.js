document.addEventListener("DOMContentLoaded", () => {
    document.documentElement.classList.add("journal-js-enabled");
    document.querySelectorAll("[data-journal-reveal]").forEach((item) => {
        item.classList.add("is-visible");
    });

    const progress = document.querySelector("[data-article-progress]");
    const articleContent = document.querySelector("[data-article-content]");
    if (!progress || !articleContent) {
        return;
    }

    let scheduled = false;
    const updateProgress = () => {
        const contentTop = articleContent.getBoundingClientRect().top + window.scrollY;
        const contentHeight = articleContent.offsetHeight;
        const scrollableHeight = contentHeight - window.innerHeight;
        const progressRatio = scrollableHeight <= 0
            ? Number(window.scrollY >= contentTop)
            : (window.scrollY - contentTop) / scrollableHeight;

        progress.style.width = `${Math.min(100, Math.max(0, progressRatio * 100))}%`;
        scheduled = false;
    };

    const scheduleUpdate = () => {
        if (!scheduled) {
            window.requestAnimationFrame(updateProgress);
            scheduled = true;
        }
    };

    window.addEventListener("scroll", scheduleUpdate, { passive: true });
    window.addEventListener("resize", scheduleUpdate);
    updateProgress();
});
