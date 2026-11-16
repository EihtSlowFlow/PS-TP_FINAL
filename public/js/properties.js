'use strict';

document.addEventListener('DOMContentLoaded', () => {
    // Rooms are required for every property type except land (HU-07 business rule).
    const typeSelect = document.getElementById('type');
    const roomsInput = document.getElementById('rooms');
    const roomsMark = document.getElementById('rooms-required-mark');

    if (typeSelect && roomsInput) {
        const syncRoomsRequirement = () => {
            const isLand = typeSelect.value === 'land';

            roomsInput.required = !isLand;
            if (roomsMark) {
                roomsMark.hidden = isLand;
            }
        };

        typeSelect.addEventListener('change', syncRoomsRequirement);
        syncRoomsRequirement();
    }

    // Live character counter for the description (limit shown to the user: 1.000 characters).
    const description = document.getElementById('description');
    const counter = document.getElementById('description-counter');

    if (description && counter) {
        const limit = description.maxLength > 0 ? description.maxLength : 1000;
        const formatter = new Intl.NumberFormat('es-AR');

        const updateCounter = () => {
            counter.textContent = `${formatter.format(description.value.length)} / ${formatter.format(limit)} caracteres`;
        };

        description.addEventListener('input', updateCounter);
        updateCounter();
    }

    // Logical property deactivation confirmation modal (HU-09 Scenarios 1, 2, 3)
    const deactivateModal = document.getElementById('deactivate-property-modal');
    const deactivateForms = document.querySelectorAll('.js-deactivate-property-form');
    if (deactivateModal && deactivateForms.length > 0) {
        const addressNode = document.getElementById('deactivate-property-address');
        const typeNode = document.getElementById('deactivate-property-type');
        const ownerNode = document.getElementById('deactivate-property-owner');
        const confirmBtn = document.getElementById('deactivate-property-confirm');
        const cancelBtn = document.getElementById('deactivate-property-cancel');
        let pendingForm = null;
        let lastTriggerButton = null;

        const closeDeactivateModal = () => {
            deactivateModal.hidden = true;
            pendingForm = null;
            if (lastTriggerButton) {
                lastTriggerButton.focus();
                lastTriggerButton = null;
            }
        };

        deactivateForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === '1') {
                    return;
                }

                event.preventDefault();
                pendingForm = form;
                lastTriggerButton = form.querySelector('button[type="submit"]');

                if (addressNode) {
                    addressNode.textContent = form.dataset.propertyAddress || 'seleccionado';
                }
                if (typeNode) {
                    typeNode.textContent = form.dataset.propertyType || 'Inmueble';
                }
                if (ownerNode) {
                    ownerNode.textContent = form.dataset.propertyOwner || '—';
                }

                deactivateModal.hidden = false;
                if (cancelBtn) {
                    cancelBtn.focus();
                }
            });
        });

        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (!pendingForm) {
                    return;
                }
                const targetForm = pendingForm;
                targetForm.dataset.confirmed = '1';
                // The server only deactivates when it receives confirmed=1 (see PropertyController::deactivate).
                const confirmedField = targetForm.querySelector('input[name="confirmed"]');
                if (confirmedField) {
                    confirmedField.value = '1';
                }
                deactivateModal.hidden = true;
                pendingForm = null;
                targetForm.submit();
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeDeactivateModal);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !deactivateModal.hidden) {
                closeDeactivateModal();
            }
        });
    }
});
