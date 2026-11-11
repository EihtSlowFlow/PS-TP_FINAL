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
            const uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const lowercase = 'abcdefghijkmnpqrstuvwxyz';
            const numbers = '23456789';
            const symbols = '!#$*';
            const charset = uppercase + lowercase + numbers + symbols;
            const randomIndex = (maximum) => {
                const range = 0x100000000;
                const limit = range - (range % maximum);
                const bytes = new Uint32Array(1);
                do {
                    window.crypto.getRandomValues(bytes);
                } while (bytes[0] >= limit);
                return bytes[0] % maximum;
            };
            const pick = (characters) => characters[randomIndex(characters.length)];
            const characters = [pick(uppercase), pick(numbers), pick(symbols)];

            while (characters.length < 12) {
                characters.push(pick(charset));
            }

            for (let index = characters.length - 1; index > 0; index -= 1) {
                const swapIndex = randomIndex(index + 1);
                [characters[index], characters[swapIndex]] = [characters[swapIndex], characters[index]];
            }

            const generated = characters.join('');

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

    const userForm = document.querySelector('.user-main-form[data-self-edit="1"]');
    const modal = document.getElementById('self-role-modal');
    if (userForm && modal) {
        const originalRole = userForm.dataset.originalRole;
        const acceptBtn = document.getElementById('self-role-accept');
        const cancelBtn = document.getElementById('self-role-cancel');
        const COOLDOWN_SECONDS = 3;
        let confirmed = false;
        let timerId = null;

        const closeModal = () => {
            window.clearInterval(timerId);
            modal.hidden = true;
        };

        userForm.addEventListener('submit', (event) => {
            const selected = userForm.querySelector('.role-radio-input:checked');
            if (confirmed || !selected || selected.value === originalRole) {
                return;
            }

            event.preventDefault();
            let remaining = COOLDOWN_SECONDS;
            acceptBtn.disabled = true;
            acceptBtn.textContent = `Aceptar (${remaining})`;
            modal.hidden = false;
            cancelBtn.focus();

            window.clearInterval(timerId);
            timerId = window.setInterval(() => {
                remaining -= 1;
                if (remaining > 0) {
                    acceptBtn.textContent = `Aceptar (${remaining})`;
                    return;
                }
                window.clearInterval(timerId);
                acceptBtn.disabled = false;
                acceptBtn.textContent = 'Aceptar';
            }, 1000);
        });

        acceptBtn.addEventListener('click', () => {
            if (acceptBtn.disabled) {
                return;
            }
            confirmed = true;
            closeModal();
            userForm.submit();
        });

        cancelBtn.addEventListener('click', closeModal);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    }

    // Logical user deactivation confirmation modal (HU-05 Scenario 1)
    const deactivateModal = document.getElementById('deactivate-user-modal');
    const deactivateForms = document.querySelectorAll('.js-deactivate-form');
    if (deactivateModal && deactivateForms.length > 0) {
        const userNameNode = document.getElementById('deactivate-user-name');
        const userRoleNode = document.getElementById('deactivate-user-role');
        const confirmBtn = document.getElementById('deactivate-user-confirm');
        const cancelBtn = document.getElementById('deactivate-user-cancel');
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

                if (userNameNode) {
                    userNameNode.textContent = form.dataset.userName || 'seleccionado';
                }
                if (userRoleNode) {
                    userRoleNode.textContent = form.dataset.userRole || 'Usuario';
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
                // The server only deactivates when it receives confirmed=1 (see UserController::deactivate).
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
