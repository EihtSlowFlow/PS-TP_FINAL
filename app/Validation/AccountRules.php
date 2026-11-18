<?php

namespace App\Validation;

/**
 * Shared validation rules for account contact details and credentials.
 */
class AccountRules
{
    /**
     * Accepts international phone formatting while requiring 6 to 15 digits.
     *
     * Allowed formatting characters are a leading plus, spaces, parentheses, periods, and hyphens.
     *
     * @param mixed $value
     */
    public function valid_phone($value): bool
    {
        if (! is_string($value) || preg_match('/\A\+?[0-9][0-9\s().-]*\z/', $value) !== 1) {
            return false;
        }

        $digits = preg_replace('/[^0-9]/', '', $value);

        return $digits !== null && strlen($digits) >= 6 && strlen($digits) <= 15;
    }

    /**
     * A first or last name: letters (accents and ñ included), separated by single spaces, apostrophes, hyphens or
     * periods ("María José", "O'Connor", "Pérez-Gómez", "Inversiones del Sur S.A."). No digits nor other symbols.
     *
     * @param mixed $value
     */
    public function valid_person_name($value): bool
    {
        return is_string($value)
            && mb_check_encoding($value, 'UTF-8')
            && preg_match('/\A[\p{L}\p{M}]+(?:[ \'’.\-][\p{L}\p{M}]+)*\.?\z/u', $value) === 1;
    }

    /**
     * Requires at least one uppercase letter, one number, and one punctuation/symbol character.
     *
     * @param mixed $value
     */
    public function strong_password($value): bool
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8')) {
            return false;
        }

        return preg_match('/\p{Lu}/u', $value) === 1
            && preg_match('/[0-9]/', $value) === 1
            && preg_match('/[\p{P}\p{S}]/u', $value) === 1;
    }
}
