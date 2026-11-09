'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password_field');
    const toggleBtn = document.getElementById('btn-toggle-password');
    const suggestBtn = document.getElementById('btn-suggest-password');

    if (passwordInput && toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleBtn.textContent = isHidden ? 'Ocultar' : 'Mostrar';
        });
    }

    if (passwordInput && suggestBtn) {
        suggestBtn.addEventListener('click', () => {
            const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!#$*';
            let generated = '';

            for (let i = 0; i < 12; i += 1) {
                const randomIndex = Math.floor(Math.random() * charset.length);
                generated += charset.charAt(randomIndex);
            }

            passwordInput.type = 'text';
            passwordInput.value = generated;
            if (toggleBtn) {
                toggleBtn.textContent = 'Ocultar';
            }
            passwordInput.focus();
        });
    }

    const roleContainer = document.getElementById('role-selector-container');
    if (roleContainer) {
        const radios = roleContainer.querySelectorAll('.role-radio-input');
        const syncSelectedCard = () => {
            radios.forEach((radio) => {
                const card = radio.closest('.role-option-card');
                if (card) {
                    card.classList.toggle('is-selected', radio.checked);
                }
            });
        };

        radios.forEach((radio) => {
            radio.addEventListener('change', syncSelectedCard);
        });
    }
});

