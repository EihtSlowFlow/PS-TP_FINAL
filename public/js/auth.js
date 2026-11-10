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
});
