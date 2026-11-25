'use strict';

/**
 * "Generar liquidaciones" (HU-19): the "select all" box and the totals of the selection. The amounts travel in whole
 * cents in data attributes, so adding them up never runs into floating point. Without JavaScript every installment
 * stays selected and the server repeats every check anyway.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('settlement-form');
    const selectAll = document.getElementById('select-all');

    if (!form || !selectAll) {
        return;
    }

    const checks = Array.from(form.querySelectorAll('.settlement-check'));
    const button = document.getElementById('btn-generate');
    const output = {
        count: document.getElementById('selected-count'),
        gross: document.getElementById('selected-gross'),
        commission: document.getElementById('selected-commission'),
        net: document.getElementById('selected-net'),
    };

    // Same format as the server: "$ 460.000" for whole amounts, "$ 28,33" otherwise.
    const formatCents = (cents) => {
        const whole = Math.trunc(cents / 100).toLocaleString('es-AR');
        const decimals = cents % 100;

        return '$ ' + whole + (decimals === 0 ? '' : ',' + String(decimals).padStart(2, '0'));
    };

    const refresh = () => {
        const selected = checks.filter((check) => check.checked);
        const sum = (key) => selected.reduce((total, check) => total + Number(check.dataset[key] || 0), 0);

        output.count.textContent = String(selected.length);
        output.gross.textContent = formatCents(sum('gross'));
        output.commission.textContent = formatCents(sum('commission'));
        output.net.textContent = formatCents(sum('net'));

        selectAll.checked = selected.length === checks.length;
        selectAll.indeterminate = selected.length > 0 && selected.length < checks.length;

        if (button) {
            button.disabled = selected.length === 0;
            button.textContent = selected.length === 1
                ? 'Generar 1 liquidación'
                : 'Generar ' + selected.length + ' liquidaciones';
        }
    };

    selectAll.hidden = false;
    selectAll.addEventListener('change', () => {
        checks.forEach((check) => {
            check.checked = selectAll.checked;
        });
        refresh();
    });
    checks.forEach((check) => check.addEventListener('change', refresh));

    form.addEventListener('submit', (event) => {
        const count = checks.filter((check) => check.checked).length;

        if (count === 0) {
            event.preventDefault();
            return;
        }

        const message = count === 1
            ? '¿Generar 1 liquidación por ' + output.net.textContent + ' neto?'
            : '¿Generar ' + count + ' liquidaciones por ' + output.net.textContent + ' neto?';

        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });

    refresh();
});
