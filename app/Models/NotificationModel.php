<?php

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

/**
 * Persistence and read-state queries for in-app user notifications (HU-22 to HU-24).
 */
class NotificationModel extends Model
{
    public const TYPE_PAYMENT_SUBMITTED        = 'payment_submitted';
    public const TYPE_PAYMENT_APPROVED         = 'payment_approved';
    public const TYPE_PAYMENT_REJECTED         = 'payment_rejected';
    public const TYPE_INSTALLMENT_DUE_SOON     = 'installment_due_soon';
    public const TYPE_CONTRACT_EXPIRING        = 'contract_expiring';
    public const TYPE_RENTAL_REQUEST_SUBMITTED = 'rental_request_submitted';
    public const TYPE_RENTAL_REQUEST_APPROVED  = 'rental_request_approved';
    public const TYPE_RENTAL_REQUEST_REJECTED  = 'rental_request_rejected';
    public const TYPE_PROPERTY_SUBMITTED       = 'property_submitted';
    public const TYPE_PROPERTY_APPROVED        = 'property_approved';
    public const TYPE_PROPERTY_REJECTED        = 'property_rejected';

    public const REF_PAYMENT        = 'payment';
    public const REF_INSTALLMENT    = 'installment';
    public const REF_CONTRACT       = 'contract';
    public const REF_RENTAL_REQUEST = 'rental_request';
    public const REF_PROPERTY       = 'property';

    public const CATEGORY_PAGOS       = 'pagos';
    public const CATEGORY_CUOTAS      = 'cuotas';
    public const CATEGORY_CONTRATOS   = 'contratos';
    public const CATEGORY_SOLICITUDES = 'solicitudes';
    public const CATEGORY_INMUEBLES   = 'inmuebles';

    /**
     * Human-readable Spanish labels for each notification type.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        self::TYPE_PAYMENT_SUBMITTED        => 'Pago informado',
        self::TYPE_PAYMENT_APPROVED         => 'Pago aprobado',
        self::TYPE_PAYMENT_REJECTED         => 'Pago rechazado',
        self::TYPE_INSTALLMENT_DUE_SOON     => 'Cuota próxima a vencer',
        self::TYPE_CONTRACT_EXPIRING        => 'Contrato por vencer',
        self::TYPE_RENTAL_REQUEST_SUBMITTED => 'Solicitud de alquiler',
        self::TYPE_RENTAL_REQUEST_APPROVED  => 'Solicitud aprobada',
        self::TYPE_RENTAL_REQUEST_REJECTED  => 'Solicitud rechazada',
        self::TYPE_PROPERTY_SUBMITTED       => 'Inmueble en revisión',
        self::TYPE_PROPERTY_APPROVED        => 'Inmueble aprobado',
        self::TYPE_PROPERTY_REJECTED        => 'Inmueble rechazado',
    ];

    /**
     * Filterable category options for the notifications center.
     *
     * @var array<string, string>
     */
    public const CATEGORY_LABELS = [
        self::CATEGORY_PAGOS       => 'Pagos y validaciones',
        self::CATEGORY_CUOTAS      => 'Vencimientos de cuotas',
        self::CATEGORY_CONTRATOS   => 'Vencimientos de contratos',
        self::CATEGORY_SOLICITUDES => 'Solicitudes de alquiler',
        self::CATEGORY_INMUEBLES   => 'Publicaciones de inmuebles',
    ];

    /**
     * Maps each category key to the notification types it includes.
     *
     * @var array<string, list<string>>
     */
    public const CATEGORY_TYPES = [
        self::CATEGORY_PAGOS => [
            self::TYPE_PAYMENT_SUBMITTED,
            self::TYPE_PAYMENT_APPROVED,
            self::TYPE_PAYMENT_REJECTED,
        ],
        self::CATEGORY_CUOTAS => [
            self::TYPE_INSTALLMENT_DUE_SOON,
        ],
        self::CATEGORY_CONTRATOS => [
            self::TYPE_CONTRACT_EXPIRING,
        ],
        self::CATEGORY_SOLICITUDES => [
            self::TYPE_RENTAL_REQUEST_SUBMITTED,
            self::TYPE_RENTAL_REQUEST_APPROVED,
            self::TYPE_RENTAL_REQUEST_REJECTED,
        ],
        self::CATEGORY_INMUEBLES => [
            self::TYPE_PROPERTY_SUBMITTED,
            self::TYPE_PROPERTY_APPROVED,
            self::TYPE_PROPERTY_REJECTED,
        ],
    ];

    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'type',
        'message',
        'reference_type',
        'reference_id',
        'read_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the Spanish label for a notification type.
     */
    public static function labelForType(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? 'Aviso';
    }

    /**
     * Returns the semantic tone (`success`, `danger`, `warning`, `info`, `neutral`) for a notification type.
     */
    public static function toneForType(string $type): string
    {
        return match ($type) {
            self::TYPE_PAYMENT_APPROVED,
            self::TYPE_RENTAL_REQUEST_APPROVED,
            self::TYPE_PROPERTY_APPROVED => 'success',
            self::TYPE_PAYMENT_REJECTED,
            self::TYPE_RENTAL_REQUEST_REJECTED,
            self::TYPE_PROPERTY_REJECTED => 'danger',
            self::TYPE_INSTALLMENT_DUE_SOON,
            self::TYPE_CONTRACT_EXPIRING => 'warning',
            self::TYPE_PAYMENT_SUBMITTED,
            self::TYPE_RENTAL_REQUEST_SUBMITTED,
            self::TYPE_PROPERTY_SUBMITTED => 'info',
            default                       => 'neutral',
        };
    }

    /**
     * Resolves the destination path (relative to site_url) for a notification given the recipient's role.
     *
     * @param array<string, mixed> $notification
     */
    public static function targetPathFor(array $notification, string $userRole): ?string
    {
        $type        = (string) ($notification['type'] ?? '');
        $referenceId = isset($notification['reference_id']) && is_numeric($notification['reference_id'])
            ? (int) $notification['reference_id']
            : null;
        $isStaff     = in_array($userRole, [UserModel::ROLE_ADMIN, UserModel::ROLE_EMPLOYEE], true);

        return match ($type) {
            self::TYPE_PAYMENT_SUBMITTED => $isStaff
                ? ($referenceId !== null ? 'admin/pagos/' . $referenceId : 'admin/pagos')
                : null,
            self::TYPE_PAYMENT_APPROVED => $userRole === UserModel::ROLE_TENANT
                ? 'inquilino/mis-pagos'
                : null,
            self::TYPE_PAYMENT_REJECTED => $userRole === UserModel::ROLE_TENANT
                ? 'inquilino/estado-cuenta'
                : null,
            self::TYPE_INSTALLMENT_DUE_SOON => $userRole === UserModel::ROLE_TENANT
                ? 'inquilino/estado-cuenta'
                : null,
            self::TYPE_CONTRACT_EXPIRING => $isStaff
                ? ($referenceId !== null ? 'admin/contratos/' . $referenceId : 'admin/contratos')
                : ($userRole === UserModel::ROLE_OWNER
                    ? ($referenceId !== null ? 'propietario/contratos/' . $referenceId : 'propietario/contratos')
                    : null),
            self::TYPE_RENTAL_REQUEST_SUBMITTED => $isStaff
                ? ($referenceId !== null ? 'admin/solicitudes/' . $referenceId : 'admin/solicitudes')
                : null,
            self::TYPE_RENTAL_REQUEST_APPROVED,
            self::TYPE_RENTAL_REQUEST_REJECTED => $userRole === UserModel::ROLE_TENANT
                ? 'inquilino/solicitudes'
                : null,
            self::TYPE_PROPERTY_SUBMITTED => $isStaff
                ? 'admin/inmuebles'
                : null,
            self::TYPE_PROPERTY_APPROVED,
            self::TYPE_PROPERTY_REJECTED => $userRole === UserModel::ROLE_OWNER
                ? 'propietario/inmuebles'
                : null,
            default => null,
        };
    }

    /**
     * Returns the action button label for opening a notification's linked screen.
     */
    public static function actionLabelFor(string $type): string
    {
        return match ($type) {
            self::TYPE_PAYMENT_SUBMITTED        => 'Revisar pago',
            self::TYPE_PAYMENT_APPROVED         => 'Ver mis pagos',
            self::TYPE_PAYMENT_REJECTED         => 'Reenviar comprobante',
            self::TYPE_INSTALLMENT_DUE_SOON     => 'Ver estado de cuenta',
            self::TYPE_CONTRACT_EXPIRING        => 'Ver contrato',
            self::TYPE_RENTAL_REQUEST_SUBMITTED => 'Ver solicitud',
            self::TYPE_RENTAL_REQUEST_APPROVED,
            self::TYPE_RENTAL_REQUEST_REJECTED  => 'Ver mis solicitudes',
            self::TYPE_PROPERTY_SUBMITTED       => 'Revisar inmuebles',
            self::TYPE_PROPERTY_APPROVED,
            self::TYPE_PROPERTY_REJECTED        => 'Ver mis inmuebles',
            default                             => 'Abrir',
        };
    }

    /**
     * Counts unread notifications for a user (uses idx_notifications_user_read_at).
     */
    public function countUnreadForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return (int) $this->builder()
            ->where('user_id', $userId)
            ->where('read_at', null)
            ->countAllResults();
    }

    /**
     * Counts total notifications for a user.
     */
    public function countAllForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return (int) $this->builder()
            ->where('user_id', $userId)
            ->countAllResults();
    }

    /**
     * Returns the most recent notifications for the header bell popover, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function findRecentForUser(int $userId, int $limit = 5): array
    {
        if ($userId <= 0) {
            return [];
        }

        return $this->builder()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Lists notifications for a user with optional read-state and category filters, newest first.
     *
     * @param 'all'|'unread'|'read' $readState
     *
     * @return list<array<string, mixed>>
     */
    public function findForUser(
        int $userId,
        string $readState = 'all',
        ?string $category = null,
        int $limit = 100
    ): array {
        if ($userId <= 0) {
            return [];
        }

        $builder = $this->builder()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit);

        if ($readState === 'unread') {
            $builder->where('read_at', null);
        } elseif ($readState === 'read') {
            $builder->where('read_at IS NOT NULL', null, false);
        }

        if ($category !== null && isset(self::CATEGORY_TYPES[$category])) {
            $builder->whereIn('type', self::CATEGORY_TYPES[$category]);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Finds a single notification only if it belongs to the given user.
     *
     * @return array<string, mixed>|null
     */
    public function findOwnedByUser(int $notificationId, int $userId): ?array
    {
        if ($notificationId <= 0 || $userId <= 0) {
            return null;
        }

        $row = $this->builder()
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Marks a single notification as read if it belongs to the given user.
     * Returns false only if the notification does not exist or belongs to another user.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = $this->findOwnedByUser($notificationId, $userId);

        if ($notification === null) {
            return false;
        }

        if ($notification['read_at'] !== null) {
            return true;
        }

        $now = Time::now()->toDateTimeString();

        return $this->builder()
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->where('read_at', null)
            ->update([
                'read_at'    => $now,
                'updated_at' => $now,
            ]) !== false;
    }

    /**
     * Marks all unread notifications of a user as read and returns how many rows were updated.
     */
    public function markAllAsRead(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $now = Time::now()->toDateTimeString();

        $this->builder()
            ->where('user_id', $userId)
            ->where('read_at', null)
            ->update([
                'read_at'    => $now,
                'updated_at' => $now,
            ]);

        return $this->db->affectedRows();
    }

    /**
     * Checks whether a notification for the same user, type and reference already exists (read or unread).
     * Used by alert generators (HU-23, HU-24) to avoid duplicate alerts.
     */
    public function existsForReference(int $userId, string $type, string $referenceType, int $referenceId): bool
    {
        return $this->builder()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->countAllResults() > 0;
    }
}

