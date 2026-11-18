<?php

namespace App\Libraries;

use InvalidArgumentException;

/**
 * Pure calculation of the rent of an installment once the contract's update percentage is applied (HU-13).
 *
 * The contract stores a monthly rent and an "incremento por actualización" (a percentage) but not how often it is
 * applied, so the rule is fixed here: the rent goes up by that percentage every 12 months of the contract, over the
 * rent of the previous year (compound). An installment of the first 12 months pays the monthly rent of the contract;
 * one of months 13 to 24, the rent plus one update; and so on. The months are counted from the month the contract
 * starts to the month of the installment's period, so the result does not depend on the order in which the
 * installments are issued.
 *
 * Like {@see LateFeeCalculator}, it works with whole numbers (cents and hundredths of a percent), never with floats,
 * and rounds half up to cents at every update, so the same inputs always give the same amount.
 */
final class RentUpdateCalculator
{
    /** Months of the contract between two updates. */
    public const UPDATE_EVERY_MONTHS = 12;

    /** Largest value of `installments.base_amount` (DECIMAL(12,2)); a larger result is capped to this one. */
    public const MAX_AMOUNT = '9999999999.99';

    private const MAX_AMOUNT_CENTS = 999999999999;

    /** Hundredths of a percent that make 100%. */
    private const FULL = 10000;

    private function __construct()
    {
    }

    /**
     * How many updates the rent has gone through at the installment's period: the whole years between the month the
     * contract starts and the month of the period (0 for the first 12 months, and for a period before the start).
     *
     * @param string $startDate "YYYY-MM-DD", the day the contract starts
     * @param string $period    "YYYY-MM-DD", the first day of the installment's month
     *
     * @throws InvalidArgumentException when a date does not look like "YYYY-MM-DD"
     */
    public static function updatesApplied(string $startDate, string $period): int
    {
        $months = self::monthNumber($period) - self::monthNumber($startDate);

        return $months > 0 ? intdiv($months, self::UPDATE_EVERY_MONTHS) : 0;
    }

    /**
     * The rent of the installment of a period, with two decimals (e.g. "275000.00").
     *
     * @param string $monthlyRent      Rent of the contract, "0.00" or more, up to two decimals
     * @param string $updatePercentage Update of the contract, 0 to 100, up to two decimals (e.g. "10.00")
     * @param string $startDate        "YYYY-MM-DD", the day the contract starts
     * @param string $period           "YYYY-MM-DD", the first day of the installment's month
     *
     * @throws InvalidArgumentException when an amount, the percentage or a date is not valid
     */
    public static function amountForPeriod(string $monthlyRent, string $updatePercentage, string $startDate, string $period): string
    {
        $cents      = self::decimalToInt($monthlyRent, 'monthly rent');
        $percentage = self::decimalToInt($updatePercentage, 'update percentage');

        if ($percentage > self::FULL) {
            throw new InvalidArgumentException('The update percentage cannot be over 100.');
        }

        for ($update = self::updatesApplied($startDate, $period); $update > 0; $update--) {
            // Round half up: floor((2 × cents × (100% + update) + 100%) ÷ (2 × 100%)), in whole numbers. The product is
            // at most 10^12 × 2 × 10^4 × 2, far inside the 64-bit range, because every step is capped.
            $cents = intdiv(2 * $cents * (self::FULL + $percentage) + self::FULL, 2 * self::FULL);

            if ($cents > self::MAX_AMOUNT_CENTS) {
                return self::MAX_AMOUNT;
            }
        }

        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * Months counted from year 0, so that two dates can be subtracted.
     */
    private static function monthNumber(string $date): int
    {
        if (preg_match('/^(\d{4})-(\d{2})-\d{2}$/', $date, $parts) !== 1) {
            throw new InvalidArgumentException("\"{$date}\" is not a \"YYYY-MM-DD\" date.");
        }

        return (int) $parts[1] * 12 + (int) $parts[2];
    }

    /**
     * "250000.00" becomes 25000000 (two decimals kept as whole units).
     */
    private static function decimalToInt(string $value, string $name): int
    {
        if (preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', trim($value), $parts) !== 1) {
            throw new InvalidArgumentException("The {$name} \"{$value}\" is not a positive number with up to two decimals.");
        }

        return (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
    }
}
