<?php

namespace App\Libraries;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Pure calculation of the late fee ("recargo por mora") of an overdue installment (HU-14).
 *
 *     late fee = base amount × (daily percentage ÷ 100) × days late
 *
 * The days late are counted from the day AFTER the due date: an installment due on the 10th is on time through the
 * 10th, and on the 11th it is one day late. The result is rounded half up to cents. The calculation is done with
 * whole numbers (cents and hundredths of a percent), never with floats, so the same inputs always give the same
 * result, on every machine and every day: that is what makes the late fee reproducible.
 *
 * There is no business cap on the accumulated late fee (the issue leaves it undefined); the only limit is the
 * technical one of the `installments.late_fee` column, {@see MAX_LATE_FEE}, so a stored value can never overflow it.
 */
final class LateFeeCalculator
{
    /** Largest value of `installments.late_fee` (DECIMAL(12,2)); a larger result is stored as this one. */
    public const MAX_LATE_FEE = '9999999999.99';

    private const MAX_LATE_FEE_CENTS = 999999999999;

    /** Cents × hundredths of a percent × days → cents: divide by 100 (percent) × 100 (hundredths of a percent). */
    private const SCALE = 10000;

    private function __construct()
    {
    }

    /**
     * Whole days between the due date and the given day, counted from the day after the due date; 0 when the due
     * date has not passed yet (on the due date itself the installment is still on time).
     *
     * @param string $dueDate "YYYY-MM-DD"
     * @param string $today   "YYYY-MM-DD", the day the fee is calculated for
     *
     * @throws InvalidArgumentException when either date is not a valid "YYYY-MM-DD" date
     */
    public static function daysLate(string $dueDate, string $today): int
    {
        $due = self::parseDate($dueDate);
        $now = self::parseDate($today);

        if ($now <= $due) {
            return 0;
        }

        return (int) $due->diff($now)->days;
    }

    /**
     * Makes sure a value is a real "YYYY-MM-DD" date.
     *
     * @throws InvalidArgumentException when it is not
     */
    public static function assertValidDate(string $value): void
    {
        self::parseDate($value);
    }

    /**
     * Whole days from the given day until the due date; 0 on the due date itself and once it has passed.
     *
     * @param string $dueDate "YYYY-MM-DD"
     * @param string $today   "YYYY-MM-DD"
     *
     * @throws InvalidArgumentException when either date is not a valid "YYYY-MM-DD" date
     */
    public static function daysUntilDue(string $dueDate, string $today): int
    {
        return self::daysLate($today, $dueDate);
    }

    /**
     * The late fee for a number of days, as a string with two decimals (e.g. "1560.00").
     *
     * @param string $baseAmount      Installment amount, "0.00" or more, up to two decimals (e.g. "390000.00")
     * @param string $dailyPercentage Daily late fee percentage of the contract, 0 to 100 with up to two decimals
     *                                (e.g. "0.50")
     * @param int    $daysLate        Days of delay, 0 or more
     *
     * @throws InvalidArgumentException when an amount, the percentage or the days are not valid
     */
    public static function lateFee(string $baseAmount, string $dailyPercentage, int $daysLate): string
    {
        if ($daysLate < 0) {
            throw new InvalidArgumentException('The days late cannot be negative.');
        }

        $baseCents  = self::decimalToInt($baseAmount, 2, 'base amount');
        $percentage = self::decimalToInt($dailyPercentage, 2, 'daily percentage');

        if ($percentage > 10000) {
            throw new InvalidArgumentException('The daily percentage cannot be over 100.');
        }

        $factor = $baseCents * $percentage; // at most 10^12 × 10^4 = 10^16, inside the 64-bit range

        if ($factor === 0 || $daysLate === 0) {
            return '0.00';
        }

        // The result would not even fit the column: avoid overflowing the integer and store the technical limit.
        if ($daysLate > intdiv(PHP_INT_MAX - self::SCALE, 2 * $factor)) {
            return self::MAX_LATE_FEE;
        }

        // Round half up: floor((2 × value + SCALE) / (2 × SCALE)), all in whole numbers.
        $cents = intdiv(2 * $factor * $daysLate + self::SCALE, 2 * self::SCALE);

        if ($cents > self::MAX_LATE_FEE_CENTS) {
            return self::MAX_LATE_FEE;
        }

        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * Late fee of an installment on a given day: the days late between the due date and `$today` times the
     * contract's daily percentage over the base amount ("0.00" while it has not passed its due date).
     *
     * @throws InvalidArgumentException when a date, an amount or the percentage is not valid
     */
    public static function forInstallment(string $baseAmount, string $dailyPercentage, string $dueDate, string $today): string
    {
        return self::lateFee($baseAmount, $dailyPercentage, self::daysLate($dueDate, $today));
    }

    /**
     * Parses a "YYYY-MM-DD" date strictly (no times, no impossible dates such as 2026-02-30).
     */
    private static function parseDate(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException("\"{$value}\" is not a valid YYYY-MM-DD date.");
        }

        return $date;
    }

    /**
     * Converts a non-negative decimal written as a string ("390000", "390000.5", "0.50") into a whole number scaled by
     * 10^$decimals (cents for amounts, hundredths for percentages), without going through floats.
     */
    private static function decimalToInt(string $value, int $decimals, string $what): int
    {
        // At most 10 integer digits, the same as the DECIMAL(12,2) columns the values come from.
        // The D modifier makes `$` match only at the very end (not before a trailing newline).
        if (preg_match('/^([0-9]{1,10})(?:\.([0-9]{1,' . $decimals . '}))?$/D', $value, $parts) !== 1) {
            throw new InvalidArgumentException("The {$what} \"{$value}\" is not a valid non-negative decimal.");
        }

        $fraction = str_pad($parts[2] ?? '', $decimals, '0');

        return (int) ($parts[1] . $fraction);
    }
}
