'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contract-form');

    if (!form) {
        return;
    }

    const propertySelect = document.getElementById('property_id');
    const ownerPreview = document.getElementById('owner-preview');
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const durationPreview = document.getElementById('duration-preview');
    const rentInput = document.getElementById('monthly_rent');
    const commissionInput = document.getElementById('commission_percentage');
    const dueSelect = document.getElementById('due_day');
    const lateFeeInput = document.getElementById('late_fee_daily_percentage');

    const summary = {
        property: document.getElementById('summary-property'),
        gross: document.getElementById('summary-gross'),
        commission: document.getElementById('summary-commission'),
        net: document.getElementById('summary-net'),
        due: document.getElementById('summary-due'),
        lateFee: document.getElementById('summary-late-fee'),
    };

    const money = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 2 });
    const NO_VALUE = '—';

    // Accepts "450000", "450000.50" and "450000,50"; anything else is not a number.
    const toNumber = (text) => {
        const value = Number.parseFloat(String(text).replace(',', '.'));

        return Number.isFinite(value) ? value : null;
    };

    const clearChildren = (element) => {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    };

    // The owner is the owner of the property: it is shown here and never sent by the form.
    const renderOwner = () => {
        const option = propertySelect.selectedOptions[0];

        clearChildren(ownerPreview);

        if (!option || option.value === '') {
            const hint = document.createElement('span');
            hint.className = 'field-help';
            hint.textContent = 'Se completa solo al elegir el inmueble: el propietario del contrato es el del inmueble.';
            ownerPreview.appendChild(hint);

            return;
        }

        const name = document.createElement('strong');
        name.textContent = option.dataset.owner || '';
        const dni = document.createElement('span');
        dni.textContent = `DNI ${option.dataset.ownerDni || ''}`;
        ownerPreview.append(name, dni);
    };

    // Whole months plus remaining days, counting the end date as part of the term (same rule as the server).
    const describeDuration = () => {
        const start = new Date(`${startInput.value}T00:00:00`);
        const end = new Date(`${endInput.value}T00:00:00`);

        if (!startInput.value || !endInput.value || Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
            return 'Duración: se calcula al cargar las dos fechas.';
        }

        end.setDate(end.getDate() + 1);

        if (end <= start) {
            return 'Duración: la fecha de finalización debe ser posterior a la de inicio.';
        }

        let months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
        const anchor = new Date(start);
        anchor.setMonth(anchor.getMonth() + months);

        if (anchor > end) {
            months -= 1;
            anchor.setTime(start.getTime());
            anchor.setMonth(anchor.getMonth() + months);
        }

        const days = Math.round((end - anchor) / 86400000);
        const monthsText = months === 1 ? '1 mes' : `${months} meses`;
        const daysText = days === 1 ? '1 día' : `${days} días`;

        if (months === 0) {
            return `Duración: ${daysText}.`;
        }

        return `Duración: ${days === 0 ? monthsText : `${monthsText} y ${daysText}`}.`;
    };

    const updateSummary = () => {
        const option = propertySelect.selectedOptions[0];
        const rent = toNumber(rentInput.value);
        const commissionPercentage = toNumber(commissionInput.value);
        const lateFeePercentage = toNumber(lateFeeInput.value);

        summary.property.textContent = option && option.value !== ''
            ? option.dataset.address || ''
            : 'Todavía no elegiste un inmueble.';
        summary.gross.textContent = rent !== null ? money.format(rent) : NO_VALUE;

        if (rent !== null && commissionPercentage !== null) {
            const commission = Math.round(rent * commissionPercentage) / 100;
            summary.commission.textContent = `${money.format(commission)} (${commissionPercentage} %)`;
            summary.net.textContent = money.format(rent - commission);
        } else {
            summary.commission.textContent = NO_VALUE;
            summary.net.textContent = NO_VALUE;
        }

        summary.due.textContent = dueSelect.value !== '' ? `Día ${dueSelect.value} de cada mes` : NO_VALUE;
        summary.lateFee.textContent = rent !== null && lateFeePercentage !== null
            ? `${money.format(Math.round(rent * lateFeePercentage) / 100)} por día`
            : NO_VALUE;

        durationPreview.textContent = describeDuration();
    };

    // The monthly rent is suggested from the property's base rent until the user types a rent of their own.
    let rentTouched = rentInput.value !== '' && !(propertySelect.selectedOptions[0]
        && rentInput.value === plainNumber(propertySelect.selectedOptions[0].dataset.baseRent));

    function plainNumber(text) {
        const value = toNumber(text || '');

        return value === null ? '' : String(value);
    }

    rentInput.addEventListener('input', () => {
        rentTouched = true;
    });

    propertySelect.addEventListener('change', () => {
        const option = propertySelect.selectedOptions[0];

        if (option && option.value !== '' && !rentTouched) {
            rentInput.value = plainNumber(option.dataset.baseRent);
        }

        renderOwner();
        updateSummary();
    });

    [startInput, endInput, rentInput, commissionInput, dueSelect, lateFeeInput].forEach((field) => {
        field.addEventListener('input', updateSummary);
        field.addEventListener('change', updateSummary);
    });

    // Prevents a double submission (a second request would be rejected anyway: the property is already rented).
    form.addEventListener('submit', () => {
        const button = document.getElementById('btn-save-contract');

        if (button) {
            window.setTimeout(() => {
                button.disabled = true;
            }, 0);
        }
    });

    renderOwner();
    updateSummary();
});
