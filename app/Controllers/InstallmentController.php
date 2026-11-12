<?php

namespace App\Controllers;

use App\Models\ContractModel;
use App\Models\InstallmentModel;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;

/**
 * Monthly installment issuing for administrators and employees (HU-13): choose a period, preview the installments
 * that will be created and confirm, for all the contracts in force that cover it or for just one of them.
 *
 * Calculating late fees belongs to HU-14 and the tenant's account statement to HU-15.
 */
class InstallmentController extends BaseController
{
    public const INVALID_PERIOD_MESSAGE   = 'Seleccioná un período válido: el mes y el año de las cuotas que querés emitir.';
    public const INVALID_CONTRACT_MESSAGE = 'El contrato elegido no existe, no está vigente o no cubre el período. No se generó ninguna cuota.';
    /** Message with the period label as its only `%s`. */
    public const NO_CONTRACTS_MESSAGE     = 'No hay contratos vigentes que cubran %s. No se generó ninguna cuota.';
    public const SAVE_ERROR_MESSAGE       = 'No pudimos emitir las cuotas por un error interno. No se guardó ninguna cuota. Intentá nuevamente.';

    /** Flash key that carries the issuing report from the POST to the report page. */
    public const REPORT_FLASH_KEY = 'installment_issue_report';

    /**
     * Renders the period form (screen ADM-09a). When a month and a year come in the query string it also shows the
     * preview: the contracts in force that cover the period and what would happen to each of them.
     */
    public function emit(): string
    {
        $monthInput = $this->request->getGet('month');
        $yearInput  = $this->request->getGet('year');
        $submitted  = $monthInput !== null || $yearInput !== null;

        $period      = $submitted ? InstallmentModel::buildPeriod($yearInput, $monthInput) : null;
        $periodError = $submitted && $period === null ? self::INVALID_PERIOD_MESSAGE : null;

        $toIssue       = [];
        $alreadyIssued = [];

        if ($period !== null) {
            foreach (model(ContractModel::class)->findInForceCoveringPeriod($period) as $row) {
                $row['due_date'] = InstallmentModel::dueDateFor($period, (int) $row['due_day']);

                if ($row['installment_id'] === null) {
                    $toIssue[] = $row;
                } else {
                    $alreadyIssued[] = $row;
                }
            }
        }

        [$selectedYear, $selectedMonth] = $this->selectedYearAndMonth($period, $submitted, $yearInput, $monthInput);

        $dueDates = array_column($toIssue, 'due_date');

        return view('admin/installments/emit', array_merge($this->panelContext(
            pageTitle: 'Emitir cuotas',
            breadcrumb: 'Cuotas / Emitir cuotas',
            activeMenu: 'cuotas'
        ), [
            'period'        => $period,
            'periodLabel'   => $period !== null ? InstallmentModel::labelForPeriod($period) : null,
            'periodError'   => $periodError,
            'selectedYear'  => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'years'         => $this->selectableYears(),
            'toIssue'       => $toIssue,
            'alreadyIssued' => $alreadyIssued,
            'totalToIssue'  => InstallmentModel::sumAmounts(array_column($toIssue, 'monthly_rent')),
            'firstDueDate'  => $dueDates === [] ? null : min($dueDates),
            'lastDueDate'   => $dueDates === [] ? null : max($dueDates),
        ]));
    }

    /**
     * Validates the period and issues the installments, for every contract that covers it or only for the one named in
     * `contract_id` (HU-13 Scenarios 1 to 5). On success it redirects to the report page.
     */
    public function issue(): RedirectResponse
    {
        $monthInput = $this->request->getPost('month');
        $yearInput  = $this->request->getPost('year');
        $period     = InstallmentModel::buildPeriod($yearInput, $monthInput);

        if ($period === null) {
            return redirect()->to('/admin/cuotas/emitir')
                ->withInput()
                ->with('error', self::INVALID_PERIOD_MESSAGE);
        }

        $previewUrl = '/admin/cuotas/emitir?' . http_build_query([
            'month' => (string) (int) substr($period, 5, 2),
            'year'  => substr($period, 0, 4),
        ]);

        $contractId  = null;
        $rawContract = $this->request->getPost('contract_id');

        if ($rawContract !== null && $rawContract !== '') {
            if (! is_string($rawContract) || preg_match('/^[1-9][0-9]{0,9}$/', $rawContract) !== 1) {
                return redirect()->to($previewUrl)->with('error', self::INVALID_CONTRACT_MESSAGE);
            }

            $contractId = (int) $rawContract;
        }

        $result = model(InstallmentModel::class)->issueForPeriod($period, $contractId);

        if ($result['error'] === InstallmentModel::ERROR_NO_ELIGIBLE_CONTRACTS) {
            $message = $contractId !== null
                ? self::INVALID_CONTRACT_MESSAGE
                : sprintf(self::NO_CONTRACTS_MESSAGE, InstallmentModel::labelForPeriod($period));

            return redirect()->to($previewUrl)->with('error', $message);
        }

        if ($result['error'] !== null) {
            return redirect()->to($previewUrl)->with('error', self::SAVE_ERROR_MESSAGE);
        }

        return redirect()->to('/admin/cuotas/emitir/resultado')
            ->with(self::REPORT_FLASH_KEY, [
                'period'        => $period,
                'single'        => $contractId !== null,
                'issued_ids'    => $result['issued_ids'],
                'issued_count'  => $result['issued_count'],
                'issued_total'  => $result['issued_total'],
                'skipped_count' => $result['skipped_count'],
                'issued_at'     => date('Y-m-d H:i:s'),
                'operator'      => (string) session()->get('user_name', ''),
            ]);
    }

    /**
     * Renders the issuing report (screen ADM-09b): how many installments were issued, how many contracts were skipped
     * because they already had one and the total to collect. The report travels in the session for one request only,
     * so reloading the page goes back to the form.
     */
    public function result(): RedirectResponse|string
    {
        $report = session()->getFlashdata(self::REPORT_FLASH_KEY);

        if (! is_array($report) || ! isset($report['period'], $report['issued_ids']) || ! is_array($report['issued_ids'])) {
            return redirect()->to('/admin/cuotas/emitir');
        }

        $installments = model(InstallmentModel::class)->findWithDetails(array_map('intval', $report['issued_ids']));

        return view('admin/installments/result', array_merge($this->panelContext(
            pageTitle: 'Resultado de la emisión',
            breadcrumb: 'Cuotas / Emitir cuotas / Resultado',
            activeMenu: 'cuotas'
        ), [
            'report'       => $report,
            'periodLabel'  => InstallmentModel::labelForPeriod((string) $report['period']),
            'installments' => $installments,
        ]));
    }

    /**
     * The year and month the selectors show: the period being previewed, what the user typed when it was invalid or
     * came back from a rejected submission, or the next month the first time the form is opened.
     *
     * @return array{0: string, 1: string}
     */
    private function selectedYearAndMonth(?string $period, bool $submitted, mixed $yearInput, mixed $monthInput): array
    {
        if ($period !== null) {
            return [substr($period, 0, 4), (string) (int) substr($period, 5, 2)];
        }

        if ($submitted) {
            return [
                is_scalar($yearInput) ? trim((string) $yearInput) : '',
                is_scalar($monthInput) ? trim((string) $monthInput) : '',
            ];
        }

        $oldYear  = old('year');
        $oldMonth = old('month');

        if (is_string($oldYear) || is_string($oldMonth)) {
            return [is_string($oldYear) ? $oldYear : '', is_string($oldMonth) ? $oldMonth : ''];
        }

        $next = new DateTimeImmutable('first day of next month');

        return [$next->format('Y'), $next->format('n')];
    }

    /**
     * The years the selector offers: from two years back to five years ahead. The server accepts any year inside the
     * limits of {@see InstallmentModel::MIN_YEAR} and {@see InstallmentModel::MAX_YEAR}.
     *
     * @return list<int>
     */
    private function selectableYears(): array
    {
        $current = (int) date('Y');

        return range(max(InstallmentModel::MIN_YEAR, $current - 2), min(InstallmentModel::MAX_YEAR, $current + 5));
    }
}
