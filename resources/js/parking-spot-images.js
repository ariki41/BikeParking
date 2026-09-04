const selectedImageKind = 'selected';
const storedImageKind = 'stored';

export const initParkingSpotImages = (root) => {
    if (root.dataset.imageFormInitialized === 'true') {
        return;
    }

    const input = root.querySelector('[data-image-input]');
    const previewList = root.querySelector('[data-image-preview-list]');
    const previewTemplate = root.querySelector('[data-image-preview-template]');
    const countLabel = root.querySelector('[data-image-count]');
    const limitError = root.querySelector('[data-image-limit-error]');

    if (!input || !previewList || !previewTemplate || !countLabel || !limitError) {
        return;
    }

    root.dataset.imageFormInitialized = 'true';

    const configuredMaxImages = Number.parseInt(root.dataset.maxImages ?? '', 10);
    const maxImages = Number.isInteger(configuredMaxImages) && configuredMaxImages > 0 ? configuredMaxImages : 4;
    let selectedFiles = [];

    const previewItems = () => [...previewList.querySelectorAll('[data-image-preview-item]')];
    const selectedPreviewItems = () => previewItems()
        .filter((item) => item.dataset.imageKind === selectedImageKind);

    const clearSelectedPreviews = () => {
        selectedPreviewItems().forEach((item) => {
            const objectUrl = item.dataset.objectUrl;
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }

            item.remove();
        });
    };

    const renumberPreviews = () => {
        previewItems().forEach((item, index) => {
            const image = item.querySelector('[data-image-preview]');
            const deleteButton = item.querySelector('[data-delete-image]');

            if (image) {
                image.alt = `駐輪場画像プレビュー ${index + 1}`;
            }

            if (deleteButton) {
                deleteButton.setAttribute('aria-label', `画像${index + 1}を削除`);
            }
        });
    };

    const syncImageCount = () => {
        const imageCount = previewItems().length;
        const exceedsLimit = imageCount > maxImages;

        previewList.classList.toggle('hidden', imageCount === 0);
        countLabel.textContent = `表示中の画像: ${imageCount} / ${maxImages}枚`;
        limitError.classList.toggle('hidden', !exceedsLimit);
        input.setCustomValidity(exceedsLimit ? `画像は合計${maxImages}枚までです。` : '');
        renumberPreviews();
    };

    const syncSelectedFiles = () => {
        const transfer = new DataTransfer();
        selectedFiles.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    };

    const showLimitError = () => {
        limitError.classList.remove('hidden');
        // 超過分は input.files へ戻さないため、現在の有効な画像まで送信不能にしない。
        input.setCustomValidity('');
    };

    const renderSelectedPreviews = () => {
        clearSelectedPreviews();

        selectedFiles.forEach((file, index) => {
            const preview = previewTemplate.content.firstElementChild?.cloneNode(true);
            if (!preview) {
                return;
            }

            const image = preview.querySelector('[data-image-preview]');
            const fileName = preview.querySelector('[data-image-name]');
            const objectUrl = URL.createObjectURL(file);

            preview.dataset.imageKind = selectedImageKind;
            preview.dataset.fileIndex = String(index);
            preview.dataset.objectUrl = objectUrl;

            if (image) {
                image.src = objectUrl;
            }

            if (fileName) {
                fileName.textContent = file.name;
            }

            previewList.appendChild(preview);
        });

        syncImageCount();
    };

    input.addEventListener('change', () => {
        const nextSelectedFiles = [...selectedFiles, ...(input.files ?? [])];

        const storedImageCount = previewItems()
            .filter((item) => item.dataset.imageKind === storedImageKind)
            .length;

        if (storedImageCount + nextSelectedFiles.length > maxImages) {
            syncSelectedFiles();
            showLimitError();

            return;
        }

        selectedFiles = nextSelectedFiles;

        syncSelectedFiles();
        renderSelectedPreviews();
    });

    previewList.addEventListener('click', (event) => {
        const deleteButton = event.target.closest('[data-delete-image]');
        if (!deleteButton || !previewList.contains(deleteButton)) {
            return;
        }

        const preview = deleteButton.closest('[data-image-preview-item]');
        if (!preview) {
            return;
        }

        if (preview.dataset.imageKind === selectedImageKind) {
            const fileIndex = Number.parseInt(preview.dataset.fileIndex ?? '', 10);
            if (!Number.isInteger(fileIndex)) {
                return;
            }

            selectedFiles.splice(fileIndex, 1);
            syncSelectedFiles();
            renderSelectedPreviews();

            return;
        }

        if (preview.dataset.imageKind === storedImageKind) {
            preview.remove();
            syncImageCount();
        }
    });

    syncImageCount();
};

const initParkingSpotImageForms = () => {
    document.querySelectorAll('[data-parking-spot-images]').forEach(initParkingSpotImages);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initParkingSpotImageForms);
} else {
    initParkingSpotImageForms();
}

document.addEventListener('livewire:navigated', initParkingSpotImageForms);
