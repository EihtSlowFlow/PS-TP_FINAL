<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;

/**
 * Central service for creating in-app notifications (HU-22, HU-23, HU-24, HU-30 to HU-33).
 *
 * Accepts an optional active database connection so callers inside a transaction
 * (such as PaymentModel) persist notifications atomically with the triggering event:
 * if the transaction rolls back, no orphaned notifications are left behind.
 */
class NotificationService
{
    private const MAX_MESSAGE_LENGTH = 500;

    /**
     * Sends a notification to a single recipient.
     */
    public function notifyUser(
        int $userId,
        string $type,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?BaseConnection $db = null
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        $connection = $db ?? Database::connect();
        $now        = Time::now()->toDateTimeString();

        return $connection->table('notifications')->insert([
            'user_id'        => $userId,
            'type'           => $type,
            'message'        => $this->truncateMessage($message),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'read_at'        => null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]) !== false;
    }

    /**
     * Sends a notification to every active Administrator and Employee user.
     *
     * @return int Number of notifications inserted.
     */
    public function notifyStaff(
        string $type,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?BaseConnection $db = null
    ): int {
        $connection = $db ?? Database::connect();
        $staffRows  = $connection->table('users')
            ->select('users.id')
            ->join('roles', 'roles.id = users.role_id')
            ->whereIn('roles.name', [UserModel::ROLE_ADMIN, UserModel::ROLE_EMPLOYEE])
            ->where('users.deleted_at', null)
            ->get()
            ->getResultArray();

        if ($staffRows === []) {
            return 0;
        }

        $now       = Time::now()->toDateTimeString();
        $truncated = $this->truncateMessage($message);
        $batch     = [];

        foreach ($staffRows as $staff) {
            $batch[] = [
                'user_id'        => (int) $staff['id'],
                'type'           => $type,
                'message'        => $truncated,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'read_at'        => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        $inserted = $connection->table('notifications')->insertBatch($batch);

        return is_int($inserted) ? $inserted : 0;
    }

    /**
     * Inserts a notification only if the same (user, type, reference_type, reference_id) does not exist yet.
     * Designed for scheduled/on-demand alerts (HU-23, HU-24).
     */
    public function notifyOnce(
        int $userId,
        string $type,
        string $message,
        string $referenceType,
        int $referenceId,
        ?BaseConnection $db = null
    ): bool {
        if ($userId <= 0 || $referenceId <= 0) {
            return false;
        }

        $connection = $db ?? Database::connect();
        $exists     = $connection->table('notifications')
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->countAllResults() > 0;

        if ($exists) {
            return false;
        }

        return $this->notifyUser($userId, $type, $message, $referenceType, $referenceId, $connection);
    }

    /**
     * HU-22 Scenario 1: notifies active admins/employees when a tenant reports a payment.
     */
    public function notifyPaymentSubmitted(
        int $paymentId,
        string $tenantFullName,
        string $periodLabel,
        string $propertyAddress,
        ?BaseConnection $db = null
    ): int {
        $tenantName = trim($tenantFullName) !== '' ? trim($tenantFullName) : 'Un inquilino';
        $message    = sprintf(
            'El inquilino %s informó un pago para la cuota de %s del inmueble %s.',
            $tenantName,
            $periodLabel,
            $propertyAddress
        );

        return $this->notifyStaff(
            NotificationModel::TYPE_PAYMENT_SUBMITTED,
            $message,
            NotificationModel::REF_PAYMENT,
            $paymentId,
            $db
        );
    }

    /**
     * HU-22 Scenario 2: notifies the tenant when their reported payment is approved.
     */
    public function notifyPaymentApproved(
        int $tenantId,
        int $paymentId,
        string $periodLabel,
        string $propertyAddress,
        ?BaseConnection $db = null
    ): bool {
        $message = sprintf(
            'Tu pago de la cuota de %s del inmueble %s fue aprobado.',
            $periodLabel,
            $propertyAddress
        );

        return $this->notifyUser(
            $tenantId,
            NotificationModel::TYPE_PAYMENT_APPROVED,
            $message,
            NotificationModel::REF_PAYMENT,
            $paymentId,
            $db
        );
    }

    /**
     * HU-22 Scenario 3: notifies the tenant when their reported payment is rejected, including the reason.
     */
    public function notifyPaymentRejected(
        int $tenantId,
        int $paymentId,
        string $periodLabel,
        string $propertyAddress,
        string $rejectionReason,
        ?BaseConnection $db = null
    ): bool {
        $reason  = trim($rejectionReason);
        $message = sprintf(
            'Tu pago de la cuota de %s del inmueble %s fue rechazado. Motivo: %s. Debés reenviar el comprobante.',
            $periodLabel,
            $propertyAddress,
            $reason
        );

        return $this->notifyUser(
            $tenantId,
            NotificationModel::TYPE_PAYMENT_REJECTED,
            $message,
            NotificationModel::REF_PAYMENT,
            $paymentId,
            $db
        );
    }

    /**
     * HU-30/31 helper: notifies staff when a tenant submits a rental request.
     */
    public function notifyRentalRequestSubmitted(
        int $requestId,
        string $tenantFullName,
        string $propertyAddress,
        ?BaseConnection $db = null
    ): int {
        $message = sprintf(
            'El inquilino %s envió una solicitud de alquiler para el inmueble %s.',
            trim($tenantFullName),
            $propertyAddress
        );

        return $this->notifyStaff(
            NotificationModel::TYPE_RENTAL_REQUEST_SUBMITTED,
            $message,
            NotificationModel::REF_RENTAL_REQUEST,
            $requestId,
            $db
        );
    }

    /**
     * HU-31 helper: notifies the tenant when their rental request is approved and converted to a contract.
     */
    public function notifyRentalRequestApproved(
        int $tenantId,
        int $requestId,
        string $propertyAddress,
        ?BaseConnection $db = null
    ): bool {
        $message = sprintf(
            'Tu solicitud de alquiler para el inmueble %s fue aprobada y ya se generó el contrato.',
            $propertyAddress
        );

        return $this->notifyUser(
            $tenantId,
            NotificationModel::TYPE_RENTAL_REQUEST_APPROVED,
            $message,
            NotificationModel::REF_RENTAL_REQUEST,
            $requestId,
            $db
        );
    }

    /**
     * HU-31 helper: notifies the tenant when their rental request is rejected.
     */
    public function notifyRentalRequestRejected(
        int $tenantId,
        int $requestId,
        string $propertyAddress,
        string $rejectionReason,
        ?BaseConnection $db = null
    ): bool {
        $message = sprintf(
            'Tu solicitud de alquiler para el inmueble %s fue rechazada. Motivo: %s.',
            $propertyAddress,
            trim($rejectionReason)
        );

        return $this->notifyUser(
            $tenantId,
            NotificationModel::TYPE_RENTAL_REQUEST_REJECTED,
            $message,
            NotificationModel::REF_RENTAL_REQUEST,
            $requestId,
            $db
        );
    }

    /**
     * HU-32 helper: notifies staff when an owner submits a property for review.
     */
    public function notifyPropertySubmitted(
        int $propertyId,
        string $ownerFullName,
        string $propertyAddress,
        ?BaseConnection $db = null
    ): int {
        $message = sprintf(
            'El propietario %s envió el inmueble %s para revisión.',
            trim($ownerFullName),
            $propertyAddress
        );

        return $this->notifyStaff(
            NotificationModel::TYPE_PROPERTY_SUBMITTED,
            $message,
            NotificationModel::REF_PROPERTY,
            $propertyId,
            $db
        );
    }

    /**
     * HU-33 helper: notifies the owner when their property is approved.
     */
    public function notifyPropertyApproved(
        int $ownerId,
        int $propertyId,
        string $propertyAddress,
        ?BaseConnection $db = null
    ): bool {
        $message = sprintf(
            'Tu inmueble %s fue aprobado y ya está publicado en el catálogo.',
            $propertyAddress
        );

        return $this->notifyUser(
            $ownerId,
            NotificationModel::TYPE_PROPERTY_APPROVED,
            $message,
            NotificationModel::REF_PROPERTY,
            $propertyId,
            $db
        );
    }

    /**
     * HU-33 helper: notifies the owner when their property is rejected.
     */
    public function notifyPropertyRejected(
        int $ownerId,
        int $propertyId,
        string $propertyAddress,
        string $rejectionReason,
        ?BaseConnection $db = null
    ): bool {
        $message = sprintf(
            'Tu inmueble %s fue rechazado. Motivo: %s.',
            $propertyAddress,
            trim($rejectionReason)
        );

        return $this->notifyUser(
            $ownerId,
            NotificationModel::TYPE_PROPERTY_REJECTED,
            $message,
            NotificationModel::REF_PROPERTY,
            $propertyId,
            $db
        );
    }

    private function truncateMessage(string $message): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $message) ?? $message);

        if (mb_strlen($clean) <= self::MAX_MESSAGE_LENGTH) {
            return $clean;
        }

        return mb_substr($clean, 0, self::MAX_MESSAGE_LENGTH - 1) . '…';
    }
}

