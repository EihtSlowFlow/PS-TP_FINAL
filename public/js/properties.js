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
});
