<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Provides the role-specific landing views and placeholder sections required by HU-01
 * until the full feature stories (HU-04, HU-15, HU-25, HU-26) are implemented.
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
     * Restricted placeholder for 'admin' only (/admin/usuarios, HU-04 / HU-05).
     */
    public function adminUsers(): string
    {
        return $this->renderPanel(
            pageTitle: 'Gestión de usuarios',
            breadcrumb: 'Usuarios',
            activeMenu: 'usuarios',
            heading: 'Usuarios',
            subtitle: 'Padrón de clientes y personal (exclusivo para Administrador).',
            allowedNote: 'Sección habilitada únicamente para el rol Administrador.'
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
        $session  = session();
        $userRole = (string) $session->get('user_role', '');
        $userName = (string) $session->get('user_name', 'Usuario');

        return view('dashboard/panel_placeholder', [
            'pageTitle'   => $pageTitle,
            'breadcrumb'  => $breadcrumb,
            'activeMenu'  => $activeMenu,
            'heading'     => $heading,
            'subtitle'    => $subtitle,
            'allowedNote' => $allowedNote,
            'userName'    => $userName,
            'userEmail'   => (string) $session->get('user_email', ''),
            'userRole'    => $userRole,
            'roleLabel'   => UserModel::labelForRole($userRole),
            'initials'    => $this->extractInitials($userName),
        ]);
    }

    private function extractInitials(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = isset($parts[0][0]) ? mb_strtoupper(mb_substr($parts[0], 0, 1)) : 'U';
        $last  = isset($parts[1][0]) ? mb_strtoupper(mb_substr($parts[1], 0, 1)) : '';

        return $first . $last;
    }
}

