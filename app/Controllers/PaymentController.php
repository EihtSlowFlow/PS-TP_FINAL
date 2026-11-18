<?php

namespace App\Controllers;

use App\Libraries\LateFeeCalculator;
use App\Libraries\ReceiptValidator;
use App\Models\ContractModel;
use App\Models\InstallmentModel;
use App\Models\PaymentModel;
use App\Models\PaymentReceiptModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;

/**
 * Tenant payment reporting and protected receipt access (HU-16).
 */
class PaymentController extends BaseController
{
    public const PAYMENT_NOT_FOUND_MESSAGE     = 'El pago solicitado no existe.';
    public const INVALID_REASON_MESSAGE        = 'Ingresá un motivo para rechazar el pago.';
    public const RESOLUTION_NOT_ALLOWED_MESSAGE = 'Solo se pueden resolver pagos pendientes de validación.';

    /**
     * Lists today’s pending validations for admins/employees (HU-17).
     */
    public function index(): string
    {
        $pending = model(PaymentModel::class)->findPendingValidationQueue();

        return view('admin/payments/index', array_merge($this->panelContext(
            pageTitle: 'Pagos',
            breadcrumb: 'Pagos / Pendientes de validación',
            activeMenu: 'pagos'
        ), [
            'payments' => $pending,
        ]));
    }

    /**
     * Shows the complete payment history for admins and employees, filtered by status and period (HU-18).
     */
    public function history(): string
    {
        $status = $this->normalizeStatusFilter($this->request->getGet('status'));
        $period = $this->normalizePeriodFilter($this->request->getGet('period'));

        return view('admin/payments/history', array_merge($this->panelContext(
            pageTitle: 'Historial de pagos',
            breadcrumb: 'Pagos / Historial',
            activeMenu: 'pagos-historial'
        ), [
            'payments' => model(PaymentModel::class)->findAdminHistory($status, $period),
            'periods'  => model(PaymentModel::class)->periodsWithPayments(),
            'filters'  => [
                'status' => $status,
                'period' => $period,
            ],
            'statusOptions' => PaymentModel::STATUS_LABELS,
        ]));
    }

    /**
     * Shows the tenant payment history (HU-18).
     */
    public function tenantHistory(): string
    {
        $tenantId = (int) session()->get('user_id');

        return view('tenant/payments/history', array_merge($this->panelContext(
            pageTitle: 'Mis pagos',
            breadcrumb: 'Estado de cuenta / Mis pagos',
            activeMenu: 'inquilino-mis-pagos'
        ), [
            'payments' => model(PaymentModel::class)->findTenantHistory($tenantId),
        ]));
    }

    public function show(int|string $paymentId): RedirectResponse|string
    {
        $payment = model(PaymentModel::class)->findForReview((int) $paymentId);

        if ($payment === null) {
            return redirect()->to(site_url('admin/pagos'))->with('error', self::PAYMENT_NOT_FOUND_MESSAGE);
        }

        if ((string) $payment['status'] !== PaymentModel::STATUS_PENDING_VALIDATION) {
            return redirect()->to(site_url('admin/pagos'))->with('error', self::RESOLUTION_NOT_ALLOWED_MESSAGE);
        }

        $installmentTotal = InstallmentModel::sumAmounts([
            $payment['base_amount'],
            $payment['late_fee'],
        ]);

        return view('admin/payments/show', array_merge($this->panelContext(
            pageTitle: 'Revisión de pago',
            breadcrumb: 'Pagos / Revisión de pago',
            activeMenu: 'pagos'
        ), [
            'payment' => $payment,
            'periodLabel' => InstallmentModel::labelForPeriod((string) $payment['period']),
            'installmentTotal' => $installmentTotal,
            'amountDifference' => PaymentModel::amountDifference((string) $payment['amount'], $installmentTotal),
            'reviewerId' => (int) session()->get('user_id'),
        ]));
    }

    /**
     * Approves a pending payment.
     */
    public function resolve(int|string $paymentId): RedirectResponse
    {
        $paymentId = (int) $paymentId;
        $userId    = (int) session()->get('user_id');

        $payment = model(PaymentModel::class)->findForReview($paymentId);
        if ($payment === null || (string) $payment['status'] !== PaymentModel::STATUS_PENDING_VALIDATION) {
            return redirect()->to(site_url('admin/pagos'))->with('error', self::RESOLUTION_NOT_ALLOWED_MESSAGE);
        }

        $result = model(PaymentModel::class)->resolvePendingPayment($paymentId, $userId, true);

        if (! $result['ok']) {
            return redirect()->to(site_url('admin/pagos/' . $paymentId))->with('error', 'No se pudo aprobar el pago. Intentá nuevamente.');
        }

        return redirect()->to(site_url('admin/pagos'))->with('success', 'Pago aprobado y cuota marcada como pagada.');
    }

    /**
     * Rejects a pending payment with a mandatory reason.
     */
    public function reject(int|string $paymentId): RedirectResponse
    {
        $paymentId = (int) $paymentId;
        $userId    = (int) session()->get('user_id');
        $reason    = trim((string) $this->request->getPost('rejection_reason'));

        $payment = model(PaymentModel::class)->findForReview($paymentId);
        if ($payment === null || (string) $payment['status'] !== PaymentModel::STATUS_PENDING_VALIDATION) {
            return redirect()->to(site_url('admin/pagos'))->with('error', self::RESOLUTION_NOT_ALLOWED_MESSAGE);
        }

        if ($reason === '') {
            return redirect()->to(site_url('admin/pagos/' . $paymentId))->with('error', self::INVALID_REASON_MESSAGE);
        }

        $result = model(PaymentModel::class)->resolvePendingPayment($paymentId, $userId, false, $reason);

        if (! $result['ok']) {
            if ($result['error'] === 'missing_reason') {
                return redirect()->to(site_url('admin/pagos/' . $paymentId))->with('error', self::INVALID_REASON_MESSAGE);
            }

            return redirect()->to(site_url('admin/pagos/' . $paymentId))->with('error', 'No se pudo rechazar el pago. Intentá nuevamente.');
        }

        return redirect()->to(site_url('admin/pagos'))->with('success', 'Pago rechazado y cuota devuelta a su estado anterior.');
    }

    /**
     * Shows the "Informar pago" form for a tenant's pending or overdue installment (HU-16).
     */
    public function create(int|string $installmentId): RedirectResponse|string
    {
        $id          = (int) $installmentId;
        $tenantId    = (int) session()->get('user_id');
        $today       = InstallmentModel::today();
        $installment = $this->loadReportableInstallment($id, $tenantId, $today);

        if ($installment instanceof RedirectResponse) {
            return $installment;
        }

        $totalDue = InstallmentModel::sumAmounts([
            $installment['base_amount'],
            $installment['late_fee'],
        ]);

        return view('tenant/payments/form', array_merge(
            $this->panelContext('Informar pago', 'Estado de cuenta / Informar pago', 'inquilino-estado-cuenta'),
            [
                'installment' => $installment,
                'periodLabel' => InstallmentModel::labelForPeriod((string) $installment['period']),
                'today'       => $today,
                'daysLate'    => LateFeeCalculator::daysLate((string) $installment['due_date'], $today),
                'totalDue'    => $totalDue,
                'methods'     => PaymentModel::METHODS,
                // Null while the contract is in force; "Contrato finalizado" / "Contrato rescindido" otherwise.
                'closedLabel' => $this->closedContractLabel((string) $installment['contract_status']),
            ]
        ));
    }

    /**
     * Validates and records a tenant payment with its digital receipt (HU-16 Scenarios 1-5).
     */
    public function store(int|string $installmentId): RedirectResponse
    {
        $id          = (int) $installmentId;
        $tenantId    = (int) session()->get('user_id');
        $today       = InstallmentModel::today();
        $installment = $this->loadReportableInstallment($id, $tenantId, $today);

        if ($installment instanceof RedirectResponse) {
            return $installment;
        }

        $closedLabel = $this->closedContractLabel((string) $installment['contract_status']);

        if ($closedLabel !== null) {
            return redirect()->to(site_url('inquilino/cuotas/' . $id . '/informar-pago'))
                ->with('error', $closedLabel . ': ya no se pueden informar pagos sobre sus cuotas.');
        }

        $input = [
            'amount'           => $this->normalizeDecimal((string) $this->request->getPost('amount')),
            'paid_on'          => trim((string) $this->request->getPost('paid_on')),
            'method'           => trim((string) $this->request->getPost('method')),
            'reference_number' => trim((string) $this->request->getPost('reference_number')),
        ];

        $storage        = service('receiptStorage');
        $file           = $this->request->getFile('receipt');
        $fileValidation = ReceiptValidator::validateUploadedFile($file, $storage);
        $errors         = $this->validatePaymentInput($input, $today, $fileValidation);

        if ($errors !== []) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $errors);
        }

        $storedName = ReceiptValidator::generateStoredName($fileValidation['extension']);

        $result = model(PaymentModel::class)->registerTenantPayment(
            $id,
            $tenantId,
            [
                'amount'           => sprintf('%.2f', (float) $input['amount']),
                'paid_on'          => $input['paid_on'],
                'method'           => $input['method'],
                'reference_number' => $input['reference_number'],
            ],
            [
                'original_name' => $fileValidation['original_name'],
                'stored_name'   => $storedName,
                'mime_type'     => $fileValidation['mime_type'],
                'size_bytes'    => $fileValidation['size_bytes'],
            ],
            static fn (): bool => $file !== null && $storage->store($file, $storedName)
        );

        if (! $result['ok']) {
            if ($result['error'] === PaymentModel::ERROR_FORBIDDEN_OR_NOT_FOUND) {
                return redirect()->to(site_url('inquilino/estado-cuenta'))
                    ->with('error', 'La cuota indicada no existe o no pertenece a tus contratos.');
            }

            if ($result['error'] === PaymentModel::ERROR_CONTRACT_CLOSED) {
                $label = $this->closedContractLabel((string) ($result['contract_status'] ?? '')) ?? 'Contrato cerrado';

                return redirect()->to(site_url('inquilino/cuotas/' . $id . '/informar-pago'))
                    ->with('error', $label . ': ya no se pueden informar pagos sobre sus cuotas.');
            }

            if ($result['error'] === PaymentModel::ERROR_INVALID_STATUS) {
                return redirect()->to(site_url('inquilino/estado-cuenta'))
                    ->with('error', 'Solo podés informar un pago sobre cuotas pendientes o vencidas.');
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo registrar el pago. Intentá nuevamente.');
        }

        $message = sprintf(
            'Pago #%d informado correctamente. Quedó Pendiente de validación y la cuota pasó a En revisión.',
            $result['payment_id']
        );

        if ($result['amount_difference'] !== '0.00') {
            $message .= ' Se registró una diferencia respecto al monto exigible para que administración la evalúe al verificarlo.';
        }

        return redirect()->to(site_url('inquilino/estado-cuenta'))
            ->with('success', $message);
    }

    /**
     * Serves a stored payment receipt only to the owning tenant or an admin/employee (HU-16).
     */
    public function receipt(int|string $receiptId): ResponseInterface
    {
        $receipt = model(PaymentReceiptModel::class)->findForAccess((int) $receiptId);

        if ($receipt === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $role           = (string) session()->get('user_role');
        $userId         = (int) session()->get('user_id');
        $isOperator     = in_array($role, [UserModel::ROLE_ADMIN, UserModel::ROLE_EMPLOYEE], true);
        $isOwningTenant = $role === UserModel::ROLE_TENANT && (int) $receipt['tenant_id'] === $userId;

        if (! $isOperator && ! $isOwningTenant) {
            return $this->response->setStatusCode(403)->setBody('Acceso denegado.');
        }

        $path = service('receiptStorage')->path((string) $receipt['stored_name']);

        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw PageNotFoundException::forPageNotFound();
        }

        $safeName = str_replace(['"', '\\', "\r", "\n"], '_', (string) $receipt['original_name']);

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', (string) $receipt['mime_type'])
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', 'inline; filename="' . $safeName . '"')
            ->setHeader('Content-Length', (string) strlen($contents))
            ->setBody($contents);
    }

    /**
     * Refreshes late fees and returns the installment if it belongs to the tenant and can receive a payment.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    private function loadReportableInstallment(int $id, int $tenantId, string $today): array|RedirectResponse
    {
        $installmentModel = model(InstallmentModel::class);
        $installmentModel->refreshLateFees($today, $id);
        $installment = $installmentModel->findCollectionDetail($id);

        if ($installment === null || (int) $installment['tenant_id'] !== $tenantId) {
            return redirect()->to(site_url('inquilino/estado-cuenta'))
                ->with('error', 'La cuota indicada no existe o no pertenece a tus contratos.');
        }

        if (! in_array((string) $installment['status'], [InstallmentModel::STATUS_PENDING, InstallmentModel::STATUS_OVERDUE], true)) {
            return redirect()->to(site_url('inquilino/estado-cuenta'))
                ->with('error', 'Solo podés informar un pago sobre cuotas pendientes o vencidas.');
        }

        return $installment;
    }

    /**
     * Validates payment fields and receipt check result on the server (HU-16 Scenarios 2-4).
     *
     * @param array{amount: string, paid_on: string, method: string, reference_number: string} $input
     * @param array{ok: bool, error?: string}                                                $fileValidation
     *
     * @return array<string, string>
     */
    private function validatePaymentInput(array $input, string $today, array $fileValidation): array
    {
        $errors = [];

        if ($input['amount'] === '' || ! is_numeric($input['amount']) || (float) $input['amount'] <= 0) {
            $errors['amount'] = 'El monto transferido debe ser un número positivo mayor a cero.';
        } elseif ((float) $input['amount'] >= 10000000000.0) {
            $errors['amount'] = 'El monto transferido supera el máximo permitido.';
        } elseif (preg_match('/\.[0-9]{3,}$/', $input['amount']) === 1) {
            $errors['amount'] = 'El monto transferido admite como máximo dos decimales.';
        }

        if ($input['paid_on'] === '') {
            $errors['paid_on'] = 'Ingresá la fecha de la operación.';
        } else {
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $input['paid_on']);

            if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $input['paid_on']) {
                $errors['paid_on'] = 'La fecha de la operación no es válida.';
            } elseif ($input['paid_on'] > $today) {
                $errors['paid_on'] = 'La fecha de la operación no puede ser futura.';
            }
        }

        if (! array_key_exists($input['method'], PaymentModel::METHODS)) {
            $errors['method'] = 'Seleccioná un medio de pago válido.';
        }

        if ($input['reference_number'] === '') {
            $errors['reference_number'] = 'Ingresá el número de comprobante o transacción.';
        } elseif (mb_strlen($input['reference_number']) > 100) {
            $errors['reference_number'] = 'El número de transacción no puede superar los 100 caracteres.';
        }

        if (! $fileValidation['ok']) {
            $errors['receipt'] = (string) ($fileValidation['error'] ?? 'El comprobante debe ser un archivo válido.');
        }

        return $errors;
    }

    /**
     * The notice shown instead of the payment form when the contract is no longer in force, or null when it is.
     */
    private function closedContractLabel(string $contractStatus): ?string
    {
        return match ($contractStatus) {
            ContractModel::STATUS_FINISHED  => 'Contrato finalizado',
            ContractModel::STATUS_RESCINDED => 'Contrato rescindido',
            default                         => null,
        };
    }

    private function normalizeStatusFilter(?string $status): ?string
    {
        $status = trim((string) $status);

        return $status !== '' && array_key_exists($status, PaymentModel::STATUS_LABELS) ? $status : null;
    }

    private function normalizePeriodFilter(?string $period): ?string
    {
        $period = trim((string) $period);

        if ($period === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $period) === 1 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $period) === 1) {
            return $period;
        }

        return null;
    }

    private function normalizeDecimal(string $value): string
    {
        $value = trim(str_replace(['$', '%', ' '], '', $value));

        if (str_contains($value, ',') && ! str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }
}
