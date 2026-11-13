<?php

namespace App\Controllers;

use App\Libraries\LateFeeCalculator;
use App\Models\ContractModel;
use App\Models\InstallmentModel;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;

/**
 * Installments for administrators and employees: monthly issuing (HU-13: choose a period, preview the installments
 * that will be created and confirm, for all the contracts in force that cover it or for just one of them) and the
 * collections screen with the automatic late fee (HU-14: the unpaid installments past their due date are marked
 * "overdue" and their fee is calculated every time they are consulted).
 *
 * The tenant's account statement belongs to HU-15; it must call `InstallmentModel::refreshLateFees()` as well.
 */
class InstallmentController extends BaseController
{
    /** Installments per page of the collections screen. */
    public const COLLECTIONS_PER_PAGE = 10;

    public const INSTALLMENT_NOT_FOUND_MESSAGE = 'La cuota solicitada no existe.';
    public const INVALID_QUERY_MESSAGE         = 'Ingresá un término de búsqueda válido, de hasta 100 caracteres.';
    public const INVALID_STATUS_FILTER_MESSAGE = 'Seleccioná un estado válido.';
    public const INVALID_PERIOD_FILTER_MESSAGE = 'Seleccioná un período válido.';

    public const INVALID_PERIOD_MESSAGE   = 'Seleccioná un período válido: el mes y el año de las cuotas que querés emitir.';
    public const INVALID_CONTRACT_MESSAGE = 'El contrato elegido no existe, no está vigente o no cubre el período. No se generó ninguna cuota.';
    /** Message with the period label as its only `%s`. */
    public const NO_CONTRACTS_MESSAGE     = 'No hay contratos vigentes que cubran %s. No se generó ninguna cuota.';
    public const SAVE_ERROR_MESSAGE       = 'No pudimos emitir las cuotas por un error interno. No se guardó ninguna cuota. Intentá nuevamente.';

    /** Flash key that carries the issuing report from the POST to the report page. */
    public const REPORT_FLASH_KEY = 'installment_issue_report';

    /**
     * Renders the collections screen (screen ADM-10, HU-14 Scenario 1): first brings the status and the late fee of
     * the unpaid installments up to date for today, then lists the installments (with their base amount, late fee and
     * total) and the totals, filtered by tenant or property, period and status.
     */
    public function index(): string
    {
        $today   = InstallmentModel::today();
        $model   = model(InstallmentModel::class);
        $refresh = $model->refreshLateFees($today);

        [$filters, $filterErrors, $page] = $this->collectionFilters();

        $pagination = $model->paginateCollections(self::COLLECTIONS_PER_PAGE, $page, $filters);
        $summary    = $model->summarizeCollections($filters);

        $items = [];

        foreach ($pagination['items'] as $row) {
            $items[] = $this->withDelayData($row, $today);
        }

        $pending     = $summary[InstallmentModel::STATUS_PENDING];
        $underReview = $summary[InstallmentModel::STATUS_UNDER_REVIEW];
        $overdue     = $summary[InstallmentModel::STATUS_OVERDUE];
        $paid        = $summary[InstallmentModel::STATUS_PAID];

        $totals = [
            'count'    => array_sum(array_column($summary, 'count')),
            'base'     => InstallmentModel::sumAmounts(array_column($summary, 'base')),
            'late_fee' => InstallmentModel::sumAmounts(array_column($summary, 'late_fee')),
        ];
        $totals['total'] = InstallmentModel::sumAmounts([$totals['base'], $totals['late_fee']]);

        $kpis = [
            // Not due yet or being checked: nothing is late.
            'to_collect' => [
                'count'  => $pending['count'] + $underReview['count'],
                'amount' => InstallmentModel::sumAmounts([$pending['base'], $pending['late_fee'], $underReview['base'], $underReview['late_fee']]),
            ],
            'collected'  => [
                'count'  => $paid['count'],
                'amount' => InstallmentModel::sumAmounts([$paid['base'], $paid['late_fee']]),
            ],
            'overdue'    => [
                'count'    => $overdue['count'],
                'amount'   => InstallmentModel::sumAmounts([$overdue['base'], $overdue['late_fee']]),
                'late_fee' => $overdue['late_fee'],
            ],
        ];

        $periods = $model->periodsWithInstallments();

        if (isset($filters['period']) && ! in_array($filters['period'], $periods, true)) {
            $periods[] = $filters['period'];
            rsort($periods);
        }

        return view('admin/installments/index', array_merge($this->panelContext(
            pageTitle: 'Cobranzas',
            breadcrumb: 'Cuotas / Cobranzas',
            activeMenu: 'cobranzas'
        ), [
            'today'        => $today,
            'refresh'      => $refresh,
            'installments' => $items,
            'pagination'   => $pagination,
            'filters'      => [
                'q'      => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
                'period' => isset($filters['period']) ? substr($filters['period'], 0, 7) : '',
            ],
            'filterErrors' => $filterErrors,
            'periods'      => $periods,
            'totals'       => $totals,
            'kpis'         => $kpis,
        ]));
    }

    /**
     * Renders the detail of one installment (HU-14 Scenarios 1 to 3): after bringing its late fee up to date, the
     * base amount, the late fee with the days and percentage it comes from, and the total, next to its data.
     */
    public function show(int|string $id): RedirectResponse|string
    {
        $id    = (int) $id;
        $today = InstallmentModel::today();
        $model = model(InstallmentModel::class);

        $model->refreshLateFees($today, $id);
        $installment = $model->findCollectionDetail($id);

        if ($installment === null) {
            return redirect()->to('/admin/cuotas')->with('error', self::INSTALLMENT_NOT_FOUND_MESSAGE);
        }

        $installment = $this->withDelayData($installment, $today);

        return view('admin/installments/show', array_merge($this->panelContext(
            pageTitle: 'Cuota #' . $installment['number'] . ' del contrato #' . $installment['contract_id'],
            breadcrumb: 'Cuotas / Cobranzas / Detalle',
            activeMenu: 'cobranzas'
        ), [
            'today'       => $today,
            'installment' => $installment,
            'periodLabel' => InstallmentModel::labelForPeriod((string) $installment['period']),
        ]));
    }

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
     * Reads and validates the filters and the page of the collections screen from the query string. A filter that is
     * not valid is ignored and reported, so the screen still lists everything else.
     *
     * @return array{0: array{q?: string, status?: string, period?: string}, 1: array<string, string>, 2: int}
     */
    private function collectionFilters(): array
    {
        $filters = [];
        $errors  = [];

        $query = $this->request->getGet('q');

        if ($query !== null && $query !== '') {
            $query = is_string($query) ? trim($query) : null;

            if ($query === null || mb_strlen($query) > 100) {
                $errors['q'] = self::INVALID_QUERY_MESSAGE;
            } elseif ($query !== '') {
                $filters['q'] = $query;
            }
        }

        $status = $this->request->getGet('status');

        if ($status !== null && $status !== '') {
            if (is_string($status) && array_key_exists($status, InstallmentModel::STATUS_LABELS)) {
                $filters['status'] = $status;
            } else {
                $errors['status'] = self::INVALID_STATUS_FILTER_MESSAGE;
            }
        }

        $period = $this->request->getGet('period');

        if ($period !== null && $period !== '') {
            if (is_string($period) && preg_match('/^([0-9]{4})-(0[1-9]|1[0-2])$/', $period, $parts) === 1
                && InstallmentModel::buildPeriod($parts[1], $parts[2]) !== null
            ) {
                $filters['period'] = InstallmentModel::buildPeriod($parts[1], $parts[2]);
            } else {
                $errors['period'] = self::INVALID_PERIOD_FILTER_MESSAGE;
            }
        }

        $page = $this->request->getGet('page');
        $page = is_scalar($page) && ctype_digit((string) $page) ? max(1, (int) $page) : 1;

        return [$filters, $errors, $page];
    }

    /**
     * Adds to an installment row the days it is late (0 while it is not past due) and the days left until its due
     * date (0 once it is due), both counted from today.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function withDelayData(array $row, string $today): array
    {
        $row['days_late']   = LateFeeCalculator::daysLate((string) $row['due_date'], $today);
        $row['days_to_due'] = LateFeeCalculator::daysUntilDue((string) $row['due_date'], $today);
        $row['total']       = InstallmentModel::sumAmounts([$row['base_amount'], $row['late_fee']]);

        return $row;
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
