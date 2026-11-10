<?php

namespace App\Controllers;

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

    /**
     * Landing panel for 'tenant' role (/inquilino/estado-cuenta).
     */
    public function tenantAccountStatus(): string
    {
        return $this->renderPanel(
            pageTitle: 'Estado de cuenta',
            breadcrumb: 'Estado de cuenta',
            activeMenu: 'inquilino-estado-cuenta',
            heading: 'Estado de cuenta',
            subtitle: 'Consulta tus cuotas emitidas, vencimientos y estado de pagos.',
            allowedNote: 'Sección habilitada para el rol Inquilino.'
        );
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
