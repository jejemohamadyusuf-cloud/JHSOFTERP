import './bootstrap';

const lockActions = (container) => {
    container.querySelectorAll('a[href]').forEach((link) => {
        link.dataset.listActionHref = link.href;
        link.removeAttribute('href');
        link.setAttribute('aria-disabled', 'true');
        link.tabIndex = -1;
    });
    container.querySelectorAll('button, input, select, textarea').forEach((control) => {
        control.disabled = true;
    });
};

const appendStandbyActions = (panel, template) => {
    if (!template) return;

    const standbyActions = document.createElement('div');
    standbyActions.dataset.listStandbyActions = '';
    standbyActions.append(template.content.cloneNode(true));
    appendPrintAction(standbyActions);
    lockActions(standbyActions);
    panel.insertBefore(standbyActions, panel.querySelector('.list-action-hint'));
};

const appendPrintAction = (container) => {
    const printButton = document.createElement('button');
    printButton.className = 'button secondary';
    printButton.type = 'button';
    printButton.dataset.listPrintAction = '';
    printButton.textContent = 'Cetak';
    container.append(printButton);
};

const initializeSelectableList = (list) => {
    const panel = list.querySelector('[data-list-actions-panel]');
    if (!panel) return;
    list.dataset.listPrintRoot = '';

    const firstTemplate = [...list.querySelectorAll('template[data-list-actions]')]
        .find((template) => template.content.querySelector('a, button, form'));
    appendStandbyActions(panel, firstTemplate);

    panel.querySelectorAll('[data-list-static-action]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!button.disabled && button.dataset.href) {
                window.location.assign(button.dataset.href);
            }
        });
    });
};

document.querySelectorAll('[data-selectable-list]').forEach(initializeSelectableList);

document.addEventListener('change', (event) => {
    const selection = event.target.closest('[data-list-select]');
    if (!selection) return;

    const list = selection.closest('[data-selectable-list]');
    const panel = list?.querySelector('[data-list-actions-panel]');
    const template = selection.closest('tr')?.querySelector('template[data-list-actions]');
    if (!panel) return;

    panel.querySelectorAll('[data-list-dynamic-actions]').forEach((actions) => actions.remove());
    panel.querySelectorAll('[data-list-standby-actions]').forEach((actions) => actions.remove());

    const hint = panel.querySelector('.list-action-hint');
    hint?.toggleAttribute('hidden', selection.checked);

    if (selection.checked) {
        const dynamicActions = document.createElement('div');
        dynamicActions.dataset.listDynamicActions = '';
        if (template) dynamicActions.append(template.content.cloneNode(true));
        appendPrintAction(dynamicActions);
        panel.insertBefore(dynamicActions, hint);
        return;
    }

    const firstTemplate = [...list.querySelectorAll('template[data-list-actions]')]
        .find((template) => template.content.querySelector('a, button, form'));
    appendStandbyActions(panel, firstTemplate);
});

document.addEventListener('click', (event) => {
    const printButton = event.target.closest('[data-list-print-action]');
    if (!printButton || printButton.disabled) return;

    const list = printButton.closest('[data-selectable-list]');
    const selectedRow = list?.querySelector('[data-list-select]:checked')?.closest('tr');
    if (!list || !selectedRow) return;

    selectedRow.dataset.listPrintSelected = '';
    const cleanup = () => {
        delete selectedRow.dataset.listPrintSelected;
    };
    window.addEventListener('afterprint', cleanup, {once: true});
    window.print();
});
