<?php

namespace App\Controllers;

use App\Models\PasswordResetModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Password recovery by single-use, expiring token (HU-03b).
 */
class PasswordResetController extends BaseController
{
    public const TOKEN_TTL_MINUTES = 60;

    /** Neutral answer for an unknown or inactive email (Scenario 4). */
    public const REQUEST_NEUTRAL_MESSAGE = 'Si el correo ingresado pertenece a una cuenta activa, podrás continuar con el restablecimiento de contraseña.';
    public const REQUEST_INVALID_EMAIL_MESSAGE = 'Ingresá un correo electrónico con formato válido.';
    public const INVALID_TOKEN_MESSAGE         = 'El enlace de restablecimiento no es válido o venció. Solicitá un nuevo restablecimiento.';
    public const RESET_SUCCESS_MESSAGE         = 'Tu contraseña fue actualizada. Ya podés iniciar sesión.';
    public const RESET_VALIDATION_MESSAGE      = 'Revisá los campos marcados antes de continuar.';

    /**
     * Renders the form asking for the account email.
     */
    public function requestForm(): string
    {
        return view('auth/forgot_password');
    }

    /**
     * Issues a token for an active user and opens the reset form directly.
     * Unknown or inactive emails keep the neutral response (Scenario 4).
     */
    public function sendLink(): RedirectResponse
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));

        $rules = [
            'email' => [
                'label'  => 'Correo electrónico',
                'rules'  => 'required|valid_email|max_length[255]',
                'errors' => [
                    'required'    => 'Ingresá tu correo electrónico.',
                    'valid_email' => self::REQUEST_INVALID_EMAIL_MESSAGE,
                    'max_length'  => 'El correo electrónico no puede superar los 255 caracteres.',
                ],
            ],
        ];

        if (! $this->validateData(['email' => $email], $rules)) {
            return redirect()->to('/auth/recuperar-password')
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('error', self::REQUEST_INVALID_EMAIL_MESSAGE);
        }

        $user = model(UserModel::class)->findActiveByEmail($email);

        if ($user !== null) {
            $token = model(PasswordResetModel::class)->issueFor((int) $user['id'], self::TOKEN_TTL_MINUTES);

            return redirect()->to('/auth/restablecer-password/' . $token);
        }

        return redirect()->to('/auth/recuperar-password')
            ->with('success', self::REQUEST_NEUTRAL_MESSAGE);
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
                'rules'  => 'required|min_length[8]|max_length[255]',
                'errors' => [
                    'required'   => 'Ingresá una contraseña.',
                    'min_length' => 'La contraseña debe tener al menos 8 caracteres.',
                    'max_length' => 'La contraseña supera la longitud permitida.',
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
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        return model(PasswordResetModel::class)->findUsableByToken($token);
    }

    private function invalidTokenRedirect(): RedirectResponse
    {
        return redirect()->to('/auth/recuperar-password')->with('error', self::INVALID_TOKEN_MESSAGE);
    }

}
