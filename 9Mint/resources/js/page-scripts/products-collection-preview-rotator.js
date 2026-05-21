// Products page: rotates fallback collection previews using the `data-images` attribute.

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.collection-preview[data-images]').forEach((img) => {
        const frame = img.closest('.collection-image-frame');
        const syncFrameBackground = () => {
            if (!frame) return;
            frame.style.setProperty('--collection-preview-bg-image', `url("${img.currentSrc || img.src}")`);
        };

        syncFrameBackground();
        img.addEventListener('load', syncFrameBackground);

        let images = [];
        try {
            images = JSON.parse(img.dataset.images || '[]');
        } catch {
            images = [];
        }

        if (!Array.isArray(images) || images.length <= 1) return;

        let index = 0;
        setInterval(() => {
            index = (index + 1) % images.length;
            img.src = images[index];
        }, 3000); // rotation ms
    });
});

