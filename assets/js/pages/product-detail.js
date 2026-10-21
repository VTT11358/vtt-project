document.addEventListener("DOMContentLoaded", () => {
    const mainImage = document.querySelector("[data-detail-main-image]");
    const thumbnails = document.querySelectorAll("[data-detail-image]");

    if (mainImage && thumbnails.length) {
        const thumbnailItems = Array.from(thumbnails);
        let activeIndex = Math.max(
            0,
            thumbnailItems.findIndex((thumbnail) => thumbnail.classList.contains("is-active"))
        );

        const showImage = (index) => {
            activeIndex = (index + thumbnailItems.length) % thumbnailItems.length;
            const activeThumbnail = thumbnailItems[activeIndex];
            const imageUrl = activeThumbnail.getAttribute("data-detail-image");

            if (imageUrl && mainImage.getAttribute("src") !== imageUrl) {
                mainImage.classList.add("is-changing");
                mainImage.addEventListener(
                    "load",
                    () => mainImage.classList.remove("is-changing"),
                    { once: true }
                );
                mainImage.addEventListener(
                    "error",
                    () => mainImage.classList.remove("is-changing"),
                    { once: true }
                );
                mainImage.src = imageUrl;
            }

            thumbnailItems.forEach((item, itemIndex) => {
                const isActive = itemIndex === activeIndex;
                item.classList.toggle("is-active", isActive);
                item.setAttribute("aria-pressed", String(isActive));
            });
            activeThumbnail.scrollIntoView({
                behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
                    ? "auto"
                    : "smooth",
                block: "nearest",
                inline: "nearest",
            });
        };

        thumbnailItems.forEach((thumbnail, index) => {
            thumbnail.addEventListener("click", () => {
                showImage(index);
            });
        });

        document.querySelectorAll("[data-detail-image-step]").forEach((button) => {
            button.addEventListener("click", () => {
                const step = Number(button.getAttribute("data-detail-image-step")) || 0;
                showImage(activeIndex + step);
            });
        });
    }

    const thumbnailTrack = document.querySelector("[data-detail-thumbnail-track]");
    if (thumbnailTrack) {
        document.querySelectorAll("[data-detail-strip-step]").forEach((button) => {
            button.addEventListener("click", () => {
                const step = Number(button.getAttribute("data-detail-strip-step")) || 0;
                const thumbnail = thumbnailTrack.querySelector(".detail-thumbnail");
                const amount = thumbnail ? thumbnail.getBoundingClientRect().width + 8 : 160;
                thumbnailTrack.scrollBy({
                    left: step * amount * 3,
                    behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
                        ? "auto"
                        : "smooth",
                });
            });
        });
    }

    const quantityInput = document.querySelector("[data-detail-quantity]");
    if (!quantityInput) {
        return;
    }

    document.querySelectorAll("[data-quantity-step]").forEach((button) => {
        button.addEventListener("click", () => {
            const min = Number(quantityInput.min) || 1;
            const max = Number(quantityInput.max) || Number.MAX_SAFE_INTEGER;
            const step = Number(button.getAttribute("data-quantity-step")) || 0;
            const current = Number(quantityInput.value) || min;
            quantityInput.value = String(Math.min(max, Math.max(min, current + step)));
            quantityInput.dispatchEvent(new Event("change", { bubbles: true }));
        });
    });

    quantityInput.addEventListener("change", () => {
        const min = Number(quantityInput.min) || 1;
        const max = Number(quantityInput.max) || Number.MAX_SAFE_INTEGER;
        const quantity = Number.parseInt(quantityInput.value, 10);
        quantityInput.value = String(
            Math.min(max, Math.max(min, Number.isFinite(quantity) ? quantity : min))
        );
    });
});
