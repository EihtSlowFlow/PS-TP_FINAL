<?php

namespace App\Controllers;

use App\Models\InstallmentModel;
use App\Models\SettlementModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Settlements paid to the owners, for the administrator only (HU-19): the screen "Generar liquidaciones" (ADM-13)
 * lists the installments already collected and not settled yet, with the commission and the net that each one would
 * produce, and generates the settlements of the chosen ones.
 *
 * The employee does not get this screen: the analysis (section 3) leaves open which administrator functions the
 * employee may use, and settlements move money, so they stay with the administrator until that is decided.
 */
class SettlementController extends BaseController
{
    public const NOTHING_SELECTED_MESSAGE = 'Seleccioná al menos una cuota cobrada para liquidar.';
    public const NOT_FOUND_MESSAGE        = 'Alguna de las cuotas elegidas no existe. No se generó ninguna liquidación.';
    /** Message with the installment description as its only `%s`. */
    public const NOT_COLLECTED_MESSAGE    = 'La cuota %s no está cobrada: no está «Pagada» o su pago no fue aprobado. No se generó ninguna liquidación.';
    /** Message with the installment description as its only `%s`. */
    public const ALREADY_SETTLED_MESSAGE  = 'La cuota %s ya tiene una liquidación generada. No se generó ninguna liquidación, para evitar duplicados.';
    public const ALREADY_SETTLED_GENERIC  = 'Una de las cuotas elegidas acaba de ser liquidada en otra operación. No se generó ninguna liquidación, para evitar duplicados.';
    public const SAVE_ERROR_MESSAGE       = 'No pudimos generar las liquidaciones por un error interno. No se guardó ninguna. Intentá nuevamente.';
    public const INVALID_OWNER_MESSAGE    = 'Seleccioná un propietario válido.';

    /** Flash key that carries the ids of the settlements just generated from the POST to the screen. */
    public const REPORT_FLASH_KEY = 'settlement_report';

    /** Most installments accepted in one request; far above any real month of the agency. */
    private const MAX_SELECTION = 500;

    /**
     * Renders the screen ADM-13: the installments ready to be settled, optionally only those of one owner, with the
     * totals; after a generation, also the settlements just created.
     */
    public function generate(): string
    {
        $model = model(SettlementModel::class);
        $rows  = $model->findSettleableInstallments();

        // The owners that have something to settle, for the filter.
        $owners = [];

        foreach ($rows as $row) {
            $ownerId = (int) $row['owner_id'];
            $owners[$ownerId] ??= [
                'id'    => $ownerId,
                'name'  => trim($row['owner_last_name'] . ', ' . $row['owner_first_name']),
                'count' => 0,
            ];
            $owners[$ownerId]['count']++;
        }

        $ownerFilter = null;
        $ownerError  = null;
        $rawOwner    = $this->request->getGet('owner');

        if ($rawOwner !== null && $rawOwner !== '') {
            if (is_string($rawOwner) && preg_match('/^[1-9][0-9]{0,9}$/', $rawOwner) === 1) {
                $ownerFilter = (int) $rawOwner;
                $rows        = array_values(array_filter($rows, static fn (array $row): bool => (int) $row['owner_id'] === $ownerFilter));
            } else {
                $ownerError = self::INVALID_OWNER_MESSAGE;
            }
        }

        $report = session()->getFlashdata(self::REPORT_FLASH_KEY);
        $generated = [];

        if (is_array($report) && isset($report['ids']) && is_array($report['ids'])) {
            $generated = $model->findWithDetails(array_map('intval', $report['ids']));
        }

        return view('admin/settlements/generate', array_merge($this->panelContext(
            pageTitle: 'Generar liquidaciones',
            breadcrumb: 'Liquidaciones / Generar liquidaciones',
            activeMenu: 'liquidaciones'
        ), [
            'today'       => InstallmentModel::today(),
            'rows'        => $rows,
            'owners'      => array_values($owners),
            'ownerFilter' => $ownerFilter,
            'ownerError'  => $ownerError,
            'totals'      => [
                'gross'      => self::sum(array_column($rows, 'gross_amount')),
                'commission' => self::sum(array_column($rows, 'commission_amount')),
                'net'        => self::sum(array_column($rows, 'net_amount')),
            ],
            'ownerCount'  => count(array_unique(array_map(static fn (array $row): int => (int) $row['owner_id'], $rows))),
            'generated'   => $generated,
        ]));
    }

    /**
     * Generates the settlements of the installments chosen in `installment_ids[]` (HU-19 Scenarios 1 to 3). Every
     * check is repeated by the model on the locked rows, so a stale page or a hand-made request cannot settle an
     * installment that is not collected or settle one twice; when any of them fails nothing is generated.
     */
    public function store(): RedirectResponse
    {
        $back = '/admin/liquidaciones/generar';
        $raw  = $this->request->getPost('installment_ids');

        if ($raw === null || $raw === '' || $raw === []) {
            return redirect()->to($back)->with('error', self::NOTHING_SELECTED_MESSAGE);
        }

        if (! is_array($raw) || count($raw) > self::MAX_SELECTION) {
            return redirect()->to($back)->with('error', self::NOT_FOUND_MESSAGE);
        }

        $ids = [];

        foreach ($raw as $value) {
            if (! is_string($value) || preg_match('/^[1-9][0-9]{0,9}$/', $value) !== 1) {
                return redirect()->to($back)->with('error', self::NOT_FOUND_MESSAGE);
            }

            $ids[] = (int) $value;
        }

        $result = model(SettlementModel::class)->generate($ids);

        if ($result['error'] !== null) {
            return redirect()->to($back)->with('error', $this->errorMessage($result['error'], $result['installment']));
        }

        $count = $result['count'];

        return redirect()->to($back)
            ->with('success', sprintf(
                '%s «Pendiente de transferencia»: bruto %s, comisión %s y neto a transferir %s.',
                $count === 1 ? 'Se generó 1 liquidación' : 'Se generaron ' . $count . ' liquidaciones',
                self::money($result['gross_total']),
                self::money($result['commission_total']),
                self::money($result['net_total'])
            ))
            ->with(self::REPORT_FLASH_KEY, ['ids' => $result['settlement_ids']]);
    }

    /**
     * The message for an error of {@see SettlementModel::generate()}, naming the installment that caused it.
     *
     * @param array<string, mixed>|null $installment
     */
    private function errorMessage(string $error, ?array $installment): string
    {
        $description = $installment === null ? null : sprintf(
            'N.º %d del contrato #%d (%s)',
            (int) $installment['number'],
            (int) $installment['contract_id'],
            InstallmentModel::labelForPeriod((string) $installment['period'])
        );

        return match ($error) {
            SettlementModel::ERROR_NOTHING_SELECTED => self::NOTHING_SELECTED_MESSAGE,
            SettlementModel::ERROR_NOT_FOUND        => self::NOT_FOUND_MESSAGE,
            SettlementModel::ERROR_NOT_COLLECTED    => sprintf(self::NOT_COLLECTED_MESSAGE, (string) $description),
            SettlementModel::ERROR_ALREADY_SETTLED  => $description === null
                ? self::ALREADY_SETTLED_GENERIC
                : sprintf(self::ALREADY_SETTLED_MESSAGE, $description),
            default => self::SAVE_ERROR_MESSAGE,
        };
    }

    /**
     * Adds up amounts with two decimals exactly.
     *
     * @param list<string> $amounts
     */
    private static function sum(array $amounts): string
    {
        $cents = 0;

        foreach ($amounts as $amount) {
            $cents += SettlementModel::toCents($amount);
        }

        return SettlementModel::fromCents($cents);
    }

    /**
     * Formats an amount for the flash message, e.g. "$ 460.000" or "$ 28,33".
     */
    private static function money(string $amount): string
    {
        $value = (float) $amount;

        return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
    }
}
