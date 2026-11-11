document.addEventListener('DOMContentLoaded', () => {
    const toggleButtons = document.querySelectorAll('[data-password-toggle]');

    toggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-password-toggle');
            const input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
        });
    });

    const passwordInputs = document.querySelectorAll('[data-password-confirmation]');
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');

    if (password && passwordConfirm && passwordInputs.length > 0) {
        const validatePasswordConfirmation = () => {
            passwordConfirm.setCustomValidity(
                passwordConfirm.value !== '' && password.value !== passwordConfirm.value
                    ? 'Las contraseñas no coinciden.'
                    : ''
            );
        };

        passwordInputs.forEach((input) => {
            input.addEventListener('input', validatePasswordConfirmation);
        });
    }

    // HU-03b: live countdown while the password-reset cooldown lasts; the server enforces it regardless.
    const cooldownAlert = document.querySelector('[data-retry-after]');
    const cooldownButton = document.querySelector('[data-retry-button]');
    if (cooldownAlert && cooldownButton) {
        let secondsLeft = parseInt(cooldownAlert.dataset.retryAfter, 10);
        const message = cooldownAlert.querySelector('span');
        const template = message ? message.textContent : '';
        const timer = window.setInterval(() => {
            secondsLeft -= 1;
            if (secondsLeft <= 0) {
                window.clearInterval(timer);
                cooldownButton.disabled = false;
                if (message) {
                    message.textContent = 'Ya podés volver a intentarlo.';
                }
                return;
            }
            if (message) {
                message.textContent = template.replace(/\d+ segundos/, secondsLeft + ' segundos');
            }
        }, 1000);
    }

    const profilePasswordForm = document.querySelector('[data-profile-password-form]');
    if (profilePasswordForm) {
        const currentPassword = profilePasswordForm.querySelector('#current_password');
        const newPassword = profilePasswordForm.querySelector('#new_password');
        const confirmation = profilePasswordForm.querySelector('#password_confirm');

        if (currentPassword && newPassword && confirmation) {
            const validateProfilePasswords = () => {
                newPassword.setCustomValidity(
                    newPassword.value !== '' && newPassword.value === currentPassword.value
                        ? 'La nueva contraseña debe ser distinta de la contraseña actual.'
                        : ''
                );
                confirmation.setCustomValidity(
                    confirmation.value !== '' && newPassword.value !== confirmation.value
                        ? 'Las contraseñas no coinciden.'
                        : ''
                );
            };

            [currentPassword, newPassword, confirmation].forEach((input) => {
                input.addEventListener('input', validateProfilePasswords);
            });
        }
    }
});
