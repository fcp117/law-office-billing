<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RecurringArrangement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SpecialBillingGenerator
{
    /**
     * Generate Queued invoices for active recurring arrangements due in the target month.
     *
     * @return array{created_invoices: int, created_items: int}
     */
    public function generateForDate(?Carbon $targetDate = null, ?int $actorUserId = null): array
    {
        $date = ($targetDate ?? Carbon::today())->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $arrangements = RecurringArrangement::with(['client', 'beneficiary'])
            ->where('status', 'Active')
            ->whereDate('start_date', '<=', $monthEnd)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->get();

        $dueBuckets = [];
        $createdItems = 0;

        foreach ($arrangements as $arrangement) {
            if (! $this->isDueInMonth($arrangement, $date)) {
                continue;
            }

            $periodInfo = $this->resolvePeriod($arrangement->billing_cycle, $date);
            $periodKey = $periodInfo['key'];

            $alreadyExists = InvoiceItem::where('recurring_arrangement_id', $arrangement->id)
                ->where('period_key', $periodKey)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $bucketKey = $arrangement->grouping_mode === 'grouped'
                ? "group_{$arrangement->client_id}_{$arrangement->type}_{$arrangement->currency}_{$periodKey}"
                : "single_{$arrangement->id}_{$periodKey}";

            $dueBuckets[$bucketKey]['meta'] = [
                'client_id'   => $arrangement->client_id,
                'matter_id'   => $arrangement->matter_id,
                'currency'    => $arrangement->currency,
                'billing_day' => $arrangement->billing_day,
                'period_key'  => $periodKey,
                'type_label'  => $arrangement->type_label,
            ];

            $dueBuckets[$bucketKey]['items'][] = [
                'arrangement'  => $arrangement,
                'period_key'   => $periodKey,
                'period_phrase' => $periodInfo['phrase'],
            ];
        }

        $createdInvoices = 0;

        foreach ($dueBuckets as $bucket) {
            DB::transaction(function () use ($bucket, $date, $actorUserId, &$createdInvoices, &$createdItems) {
                $meta = $bucket['meta'];
                $day = min((int) $meta['billing_day'], $date->daysInMonth);
                $issueDate = $date->copy()->day($day);
                $dueDate = $issueDate->copy()->addDays(30);

                $totalInvoiceAmount = 0.0;
                $preparedItems = [];

                foreach ($bucket['items'] as $entry) {
                    /** @var RecurringArrangement $arr */
                    $arr = $entry['arrangement'];
                    $baseAmount = round((float) $arr->amount, 2);
                    $vat = $arr->is_vatable ? round($baseAmount * 0.12, 2) : 0.00;
                    $totalInvoiceAmount += ($baseAmount + $vat);

                    $lineDesc = $this->formatLineDescription($arr, $entry['period_phrase']);

                    $preparedItems[] = [
                        'recurring_arrangement_id' => $arr->id,
                        'beneficiary_user_id'      => $arr->beneficiary_user_id,
                        'period_key'               => $entry['period_key'],
                        'description'              => $lineDesc,
                        'amount'                   => $baseAmount,
                        'vat_amount'               => $vat,
                    ];
                }

                $invoice = Invoice::create([
                    'matter_id'      => $meta['matter_id'],
                    'client_id'      => $meta['client_id'],
                    'invoice_number' => 'INV-' . $issueDate->format('Ymd') . '-' . rand(1000, 9999),
                    'currency'       => $meta['currency'],
                    'billing_source' => 'special_billing',
                    'period_key'     => $meta['period_key'],
                    'issue_date'     => $issueDate,
                    'due_date'       => $dueDate,
                    'total_amount'   => round($totalInvoiceAmount, 2),
                    'status'         => 'Queued',
                    'remarks'        => 'Please make checks payable to Ortega, Bacorro, Odulio, Calma & Carbonell.',
                    'created_by'     => $actorUserId,
                ]);

                foreach ($preparedItems as $itemData) {
                    $invoice->items()->create($itemData);
                    $createdItems++;
                }

                $createdInvoices++;
            });
        }

        return [
            'created_invoices' => $createdInvoices,
            'created_items'    => $createdItems,
        ];
    }

    private function formatLineDescription(RecurringArrangement $arr, string $periodPhrase): string
    {
        $typeLabel = $arr->type_label; // "Retainer Fee", "Secondment Fee", or "Director's Fee"
        $lawyerName = $arr->beneficiary?->name;

        $text = $lawyerName
            ? "{$typeLabel} for {$lawyerName} {$periodPhrase}"
            : "{$typeLabel} {$periodPhrase}";

        // Append custom schedule/contract note if it adds extra detail beyond the generic type label
        $cleanNote = trim((string) $arr->description);
        $genericDefaults = [
            'retainer fee',
            'monthly general retainer fee',
            'secondment fee',
            'legal secondment fee (on-site schedule)',
            "director's fee",
        ];

        if ($cleanNote !== '' && ! in_array(strtolower($cleanNote), $genericDefaults, true)) {
            $text .= " ({$cleanNote})";
        }

        return $text;
    }

    private function isDueInMonth(RecurringArrangement $arrangement, Carbon $targetMonth): bool
    {
        $start = $arrangement->start_date->copy()->startOfMonth();
        $monthsDiff = ($targetMonth->year - $start->year) * 12 + ($targetMonth->month - $start->month);

        if ($monthsDiff < 0) {
            return false;
        }

        return match ($arrangement->billing_cycle) {
            'quarterly'   => $monthsDiff % 3 === 0,
            'semi_annual' => $monthsDiff % 6 === 0,
            'annual'      => $monthsDiff % 12 === 0,
            default       => true,
        };
    }

    private function resolvePeriod(string $cycle, Carbon $date): array
    {
        return match ($cycle) {
            'quarterly' => [
                'key'    => $date->year . '-Q' . $date->quarter,
                'phrase' => 'for Q' . $date->quarter . ' ' . $date->year,
            ],
            'semi_annual' => [
                'key'    => $date->year . '-H' . ($date->month <= 6 ? 1 : 2),
                'phrase' => 'for ' . ($date->month <= 6 ? 'H1 (Jan–Jun) ' : 'H2 (Jul–Dec) ') . $date->year,
            ],
            'annual' => [
                'key'    => (string) $date->year,
                'phrase' => 'for FY ' . $date->year,
            ],
            default => [
                'key'    => $date->format('Y-m'),
                'phrase' => 'for the month of ' . $date->format('F Y'),
            ],
        };
    }
}