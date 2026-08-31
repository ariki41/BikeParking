const disabledInputClasses = ['bg-slate-100', 'text-slate-500', 'cursor-not-allowed'];

const setInputDisabledAppearance = (input, disabled) => {
    disabledInputClasses.forEach((className) => input.classList.toggle(className, disabled));
    input.classList.toggle('bg-white', !disabled);
};

const syncFreeMinutesState = (item) => {
    const checkbox = item.querySelector('.no-free-minutes-checkbox');
    const input = item.querySelector('.free-minutes-input');

    if (!checkbox || !input) {
        return;
    }

    input.readOnly = checkbox.checked;
    setInputDisabledAppearance(input, checkbox.checked);

    if (checkbox.checked) {
        input.value = '0';
    }
};

const syncMaxRateState = (item) => {
    const checkbox = item.querySelector('.no-max-rate-checkbox');
    const input = item.querySelector('.max-rate-input');

    if (!checkbox || !input) {
        return;
    }

    input.disabled = checkbox.checked;
    setInputDisabledAppearance(input, checkbox.checked);

    if (checkbox.checked) {
        input.value = '';
    }
};

export const initParkingSpotRates = (root) => {
    if (root.dataset.rateFormInitialized === 'true') {
        return;
    }

    const list = root.querySelector('[data-rate-list]');
    const template = root.querySelector('[data-rate-template]');
    const addButton = root.querySelector('[data-add-rate]');

    if (!list || !template || !addButton) {
        return;
    }

    root.dataset.rateFormInitialized = 'true';

    const configuredMaxRates = Number.parseInt(root.dataset.maxRates ?? '', 10);
    const maxRates = Number.isInteger(configuredMaxRates) && configuredMaxRates > 0 ? configuredMaxRates : 4;
    const rateItems = () => [...list.querySelectorAll('[data-rate-item]')];

    const renumberRates = () => {
        const items = rateItems();

        items.forEach((item, index) => {
            item.querySelector('.rate-number').textContent = index + 1;
            item.querySelectorAll('[data-rate-field]').forEach((field) => {
                field.name = `rates[${index}][${field.dataset.rateField}]`;
            });
            syncFreeMinutesState(item);
            syncMaxRateState(item);
        });

        list.querySelectorAll('[data-delete-rate]').forEach((button) => {
            button.classList.toggle('hidden', items.length <= 1);
            button.disabled = items.length <= 1;
        });

        addButton.classList.toggle('hidden', items.length >= maxRates);
        addButton.disabled = items.length >= maxRates;
    };

    addButton.addEventListener('click', () => {
        if (rateItems().length >= maxRates) {
            return;
        }

        const newItem = template.content.firstElementChild?.cloneNode(true);
        if (!newItem) {
            return;
        }

        list.appendChild(newItem);
        renumberRates();
    });

    list.addEventListener('click', (event) => {
        const deleteButton = event.target.closest('[data-delete-rate]');
        if (!deleteButton || !list.contains(deleteButton) || rateItems().length <= 1) {
            return;
        }

        deleteButton.closest('[data-rate-item]')?.remove();
        renumberRates();
    });

    list.addEventListener('change', (event) => {
        const checkbox = event.target.closest('.no-max-rate-checkbox, .no-free-minutes-checkbox');
        if (!checkbox || !list.contains(checkbox)) {
            return;
        }

        const item = checkbox.closest('[data-rate-item]');
        if (!item) {
            return;
        }

        syncFreeMinutesState(item);
        syncMaxRateState(item);
    });

    renumberRates();
};

const initParkingSpotRateForms = () => {
    document.querySelectorAll('[data-parking-spot-rates]').forEach(initParkingSpotRates);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initParkingSpotRateForms);
} else {
    initParkingSpotRateForms();
}

document.addEventListener('livewire:navigated', initParkingSpotRateForms);
