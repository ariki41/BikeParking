const init = (root) => {
    if (root.dataset.initialized) return;
    root.dataset.initialized = 'true';
    const list = root.querySelector('[data-business-hour-list]'); const template = root.querySelector('[data-business-hour-template]'); const add = root.querySelector('[data-add-business-hour]');
    const items = () => [...list.querySelectorAll('[data-business-hour-item]')];
    const sync = () => { items().forEach((item, i) => { item.querySelector('.business-hour-number').textContent = i + 1; item.querySelectorAll('[data-business-hour-field]').forEach(field => field.name = `business_hours[${i}][${field.dataset.businessHourField}]`); }); add.hidden = items().length >= 8; items().forEach(item => item.querySelector('[data-delete-business-hour]').hidden = items().length <= 1); };
    add.addEventListener('click', () => { if (items().length < 8) { list.append(template.content.firstElementChild.cloneNode(true)); sync(); } });
    list.addEventListener('click', event => { const button = event.target.closest('[data-delete-business-hour]'); if (button && items().length > 1) { button.closest('[data-business-hour-item]').remove(); sync(); } });
    list.addEventListener('change', event => { if (event.target.matches('.business-hour-closed')) sync(); }); sync();
};
const all = () => document.querySelectorAll('[data-business-hours]').forEach(init);
document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', all) : all(); document.addEventListener('livewire:navigated', all);
