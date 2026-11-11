<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Single-use, expiring password reset tokens (HU-03b).
 *
 * Only the SHA-256 hash of a token is stored; the plain token exists just in the link sent to the user.
 */
class PasswordResetModel extends Model
{
    protected $table            = 'password_resets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'token_hash', 'expires_at', 'used_at'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the value persisted for a plain token.
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Creates a new token for the user, invalidating any previous one, and returns the plain token.
     */
    public function issueFor(int $userId, int $ttlMinutes): string
    {
        $token = bin2hex(random_bytes(32));

        $this->db->transStart();

        $this->invalidateAllFor($userId);
        $this->insert([
            'user_id'    => $userId,
            'token_hash' => self::hashToken($token),
            'expires_at' => date('Y-m-d H:i:s', time() + ($ttlMinutes * 60)),
            'used_at'    => null,
        ]);

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Could not create a password reset token.');
        }

        return $token;
    }

    /**
     * Finds a token that is unused, not expired and belongs to an active (not deactivated) user.
     *
     * @return array<string, mixed>|null
     */
    public function findUsableByToken(string $token): ?array
    {
        $row = $this->builder()
            ->select('password_resets.*')
            ->join('users', 'users.id = password_resets.user_id')
            ->where('password_resets.token_hash', self::hashToken($token))
            ->where('password_resets.used_at', null)
            ->where('password_resets.expires_at >', date('Y-m-d H:i:s'))
            ->where('users.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Marks a current token as used. Returns false when it was already used or expired.
     */
    public function consume(int $id): bool
    {
        $now = date('Y-m-d H:i:s');

        $this->builder()
            ->where('id', $id)
            ->where('used_at', null)
            ->where('expires_at >', $now)
            ->update(['used_at' => $now, 'updated_at' => $now]);

        return $this->db->affectedRows() === 1;
    }

    /**
     * Invalidates every pending token of a user.
     */
    public function invalidateAllFor(int $userId): void
    {
        $now = date('Y-m-d H:i:s');

        $this->builder()
            ->where('user_id', $userId)
            ->where('used_at', null)
            ->update(['used_at' => $now, 'updated_at' => $now]);
    }
}
