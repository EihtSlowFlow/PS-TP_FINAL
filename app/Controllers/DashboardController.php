<?php

namespace App\Controllers;

use App\Libraries\LateFeeCalculator;
use App\Models\InstallmentModel;
use App\Models\PaymentModel;

/**
 * Provides the role-specific landing views required by HU-01 until the full feature
 * stories (HU-15, HU-25, HU-26) replace these placeholders.
 */
class DashboardController extends BaseController
{
    /**
     * Landing panel for 'admin' and 'employee' roles (/admin/dashboard).
     */
    public function adminDashboard(): string
    {
        return $this->renderPanel(
            pageTitle: 'Panel de control',
            breadcrumb: 'Dashboard',
            activeMenu: 'dashboard',
            heading: 'Panel de control',
            subtitle: 'Resumen operativo de administración inmobiliaria.',
            allowedNote: 'Sección habilitada para los roles Administrador y Empleado.'
        );
    }

    /**
     * Landing panel for 'owner' role (/propietario/dashboard).
     */
    public function ownerDashboard(): string
    {
        return $this->renderPanel(
            pageTitle: 'Dashboard del propietario',
            breadcrumb: 'Dashboard',
            activeMenu: 'propietario-dashboard',
            heading: 'Panel del propietario',
            subtitle: 'Resumen de inmuebles administrados, contratos vigentes y liquidaciones.',
            allowedNote: 'Sección habilitada para el rol Propietario.'
        );
    }

    /** Tenant account statement (HU-15). */
    public function tenantAccountStatus(): string
    {
        $today = InstallmentModel::today();
        $model = model(InstallmentModel::class);
        $model->refreshLateFees($today);

        $installments = $model->findTenantAccount((int) session()->get('user_id'));
        $payments     = model(PaymentModel::class)->findTenantAccountDetails(
            array_map(static fn (array $row): int => (int) $row['id'], $installments)
        );
        $byStatus     = array_fill_keys(array_keys(InstallmentModel::STATUS_LABELS), []);

        foreach ($installments as $installment) {
            $installment['days_late']   = LateFeeCalculator::daysLate((string) $installment['due_date'], $today);
            $installment['days_to_due'] = LateFeeCalculator::daysUntilDue((string) $installment['due_date'], $today);
            $installment['total']       = InstallmentModel::sumAmounts([
                $installment['base_amount'],
                $installment['late_fee'],
            ]);
            $installment['payment'] = $payments[(int) $installment['id']] ?? null;
            $byStatus[(string) $installment['status']][] = $installment;
        }

        return view('dashboard/tenant_account_status', array_merge(
            $this->panelContext('Estado de cuenta', 'Estado de cuenta', 'inquilino-estado-cuenta'),
            [
                'byStatus'    => $byStatus,
                'totalCount'  => count($installments),
                'statusOrder' => [
                    InstallmentModel::STATUS_PENDING,
                    InstallmentModel::STATUS_OVERDUE,
                    InstallmentModel::STATUS_UNDER_REVIEW,
                    InstallmentModel::STATUS_PAID,
                ],
            ]
        ));
    }

    private function renderPanel(
        string $pageTitle,
        string $breadcrumb,
        string $activeMenu,
        string $heading,
        string $subtitle,
        string $allowedNote
    ): string {
        return view('dashboard/panel_placeholder', array_merge(
            $this->panelContext($pageTitle, $breadcrumb, $activeMenu),
            [
                'heading'     => $heading,
                'subtitle'    => $subtitle,
                'allowedNote' => $allowedNote,
            ]
        ));
    }
}
