<?php

namespace App\Controllers;

use App\Libraries\ResetAttemptLimiter;
use App\Models\PasswordResetModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Password recovery by single-use, expiring token (HU-03b).
 *
 * Accepted design decisions (agreed with the instructor; see .docs/exec-plans/HU-03b_handoff.md):
 * - No external channel (email/SMS) is used, so the token is handed over in the same response.
 * - To reduce the account-takeover risk that follows, the request needs two data of the account (email + DNI).
 * - Any non-matching combination (unknown email, deactivated user, or wrong DNI) is rejected on the request screen
 *   with the same generic message ("Las credenciales ingresadas son incorrectas."), without revealing which piece failed.
 * - On the third non-matching attempt (per IP and per email), a 2-minute cooldown is activated immediately.
 */
class PasswordResetController extends BaseController
{
    public const TOKEN_TTL_MINUTES = 60;

    public const INVALID_CREDENTIALS_MESSAGE   = 'Las credenciales ingresadas son incorrectas.';
    public const ACCOUNT_DISABLED_MESSAGE      = 'Usuario dado de baja, hable con un administrador.';
    public const REQUEST_INVALID_DATA_MESSAGE  = 'Revisá el correo electrónico y el DNI ingresados.';
    public const COOLDOWN_MESSAGE              = 'Hiciste demasiados intentos. Por seguridad, esperá %d segundos antes de volver a intentarlo.';
    public const INVALID_TOKEN_MESSAGE         = 'El enlace de restablecimiento no es válido o venció. Solicitá un nuevo restablecimiento.';
    public const RESET_SUCCESS_MESSAGE         = 'Tu contraseña fue actualizada. Ya podés iniciar sesión.';
    public const RESET_VALIDATION_MESSAGE      = 'Revisá los campos marcados antes de continuar.';

    /**
     * Renders the form asking for the account email and DNI.
     */
    public function requestForm(): string
    {
        return view('auth/forgot_password');
    }

    /**
     * Issues a token when email and DNI match an active user and opens the reset form directly.
     * Any non-matching combination (Scenario 4) stays on the request form with a generic error,
     * and the third failed attempt triggers a 2-minute cooldown.
     */
    public function sendLink(): RedirectResponse
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $dni   = $this->normalizeDni((string) $this->request->getPost('document_number'));

        // Cooldown first: while it lasts nothing is looked up, so a locked key is no oracle.
        $limiterKeys = ['ip:' . $this->request->getIPAddress(), 'email:' . $email];
        $limiter     = new ResetAttemptLimiter();
        $lockedFor   = $limiter->secondsLocked($limiterKeys);

        if ($lockedFor > 0) {
            return redirect()->to('/auth/recuperar-password')
                ->withInput()
                ->with('error', sprintf(self::COOLDOWN_MESSAGE, $lockedFor))
                ->with('retry_after', $lockedFor);
        }

        $rules = [
            'email' => [
                'label'  => 'Correo electrónico',
                'rules'  => 'required|valid_email|max_length[255]',
                'errors' => [
                    'required'    => 'Ingresá tu correo electrónico.',
                    'valid_email' => 'Ingresá un correo electrónico con formato válido.',
                    'max_length'  => 'El correo electrónico no puede superar los 255 caracteres.',
                ],
            ],
            'document_number' => [
                'label'  => 'DNI',
                'rules'  => 'required|alpha_numeric|max_length[20]',
                'errors' => [
                    'required'      => 'Ingresá tu DNI.',
                    'alpha_numeric' => 'El DNI solo debe contener letras o números, sin símbolos.',
                    'max_length'    => 'El DNI no puede superar los 20 caracteres.',
                ],
            ],
        ];

        // Format-only validation: it says nothing about which accounts exist.
        if (! $this->validateData(['email' => $email, 'document_number' => $dni], $rules)) {
            return redirect()->to('/auth/recuperar-password')
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('error', self::REQUEST_INVALID_DATA_MESSAGE);
        }

        $userModel = model(UserModel::class);
        $user      = $userModel->findActiveByEmail($email);

        if ($user !== null && hash_equals(strtolower((string) $user['document_number']), strtolower($dni))) {
            $token = model(PasswordResetModel::class)->issueFor((int) $user['id'], self::TOKEN_TTL_MINUTES);
            // Only the email key is cleared: clearing the IP key would let a guesser reset its own counter.
            $limiter->clear(['email:' . $email]);

            return redirect()->to('/auth/restablecer-password/' . $token);
        }

        if ($user === null) {
            $deactivatedUser = $userModel->findDeactivatedByEmail($email);

            if ($deactivatedUser !== null && hash_equals(strtolower((string) $deactivatedUser['document_number']), strtolower($dni))) {
                return redirect()->to('/auth/recuperar-password')
                    ->withInput()
                    ->with('error', self::ACCOUNT_DISABLED_MESSAGE);
            }
        }

        $limiter->registerFailure($limiterKeys);
        $lockedFor = $limiter->secondsLocked($limiterKeys);

        if ($lockedFor > 0) {
            return redirect()->to('/auth/recuperar-password')
                ->withInput()
                ->with('error', sprintf(self::COOLDOWN_MESSAGE, $lockedFor))
                ->with('retry_after', $lockedFor);
        }

        return redirect()->to('/auth/recuperar-password')
            ->withInput()
            ->with('error', self::INVALID_CREDENTIALS_MESSAGE);
    }

    /**
     * Renders the new-password form when the token is still usable (Scenarios 2 and 3).
     */
    public function resetForm(string $token): RedirectResponse|string
    {
        if ($this->usableToken($token) === null) {
            return $this->invalidTokenRedirect();
        }

        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        $this->response->setHeader('Referrer-Policy', 'no-referrer');

        return view('auth/reset_password', ['token' => $token]);
    }

    /**
     * Sets the new password, burns the token and sends the user to the login screen (Scenario 2).
     */
    public function updatePassword(string $token): RedirectResponse
    {
        $password        = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        // Never persist the submitted passwords in session old-input flashdata.
        service('superglobals')->unsetPost('password');
        service('superglobals')->unsetPost('password_confirm');

        $reset = $this->usableToken($token);
        if ($reset === null) {
            return $this->invalidTokenRedirect();
        }

        $rules = [
            'password' => [
                'label'  => 'Contraseña',
                'rules'  => 'required|min_length[8]|max_length[255]|strong_password',
                'errors' => [
                    'required'        => 'Ingresá una contraseña.',
                    'min_length'      => 'La contraseña debe tener al menos 8 caracteres.',
                    'max_length'      => 'La contraseña supera la longitud permitida.',
                    'strong_password' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.',
                ],
            ],
            'password_confirm' => [
                'label'  => 'Repetir contraseña',
                'rules'  => 'required|matches[password]',
                'errors' => [
                    'required' => 'Repetí tu contraseña.',
                    'matches'  => 'Las contraseñas no coinciden.',
                ],
            ],
        ];

        if (! $this->validateData(['password' => $password, 'password_confirm' => $passwordConfirm], $rules)) {
            return redirect()->to('/auth/restablecer-password/' . $token)
                ->with('errors', $this->validator->getErrors())
                ->with('error', self::RESET_VALIDATION_MESSAGE);
        }

        $resets = model(PasswordResetModel::class);
        $db     = db_connect();

        $db->transStart();

        // Atomic single use: only the request that flips used_at may change the password.
        $consumed = $resets->consume((int) $reset['id']);
        if ($consumed) {
            model(UserModel::class)->update((int) $reset['user_id'], [
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            ]);
            $resets->invalidateAllFor((int) $reset['user_id']);
        }

        $db->transComplete();

        if (! $consumed || ! $db->transStatus()) {
            return $this->invalidTokenRedirect();
        }

        return redirect()->to('/auth/login')->with('success', self::RESET_SUCCESS_MESSAGE);
    }

    /**
     * Returns the token record only when the string looks like one of ours and is still usable.
     *
     * @return array<string, mixed>|null
     */
    private function usableToken(string $token): ?array
    {
        if (! $this->isWellFormedToken($token)) {
            return null;
        }

        return model(PasswordResetModel::class)->findUsableByToken($token);
    }

    private function isWellFormedToken(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    /**
     * Same DNI normalization as the public registration (dots, spaces and hyphens removed).
     */
    private function normalizeDni(string $rawDni): string
    {
        $rawDni = trim($rawDni);

        return preg_replace('/[\.\s-]+/', '', $rawDni) ?? $rawDni;
    }

    private function invalidTokenRedirect(): RedirectResponse
    {
        return redirect()->to('/auth/recuperar-password')->with('error', self::INVALID_TOKEN_MESSAGE);
    }

}
