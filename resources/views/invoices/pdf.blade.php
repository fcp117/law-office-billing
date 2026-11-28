<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Account - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; color: #333; font-size: 12px; line-height: 1.4; margin: 0; padding: 0; }
        .header-table, .details-table, .items-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .firm-name { font-size: 18px; font-weight: bold; text-transform: uppercase; color: #1e293b; }
        .firm-address, .invoice-meta { font-size: 11px; color: #64748b; }
        .invoice-title { font-size: 20px; font-weight: bold; text-align: right; color: #0f172a; text-transform: uppercase; margin-bottom: 5px; }

        .box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; margin-top: 20px; }
        .details-table td { padding: 4px 8px; font-size: 11px; }

        .items-table { margin-top: 30px; }
        .items-table th { background-color: #0f172a; color: #fff; text-transform: uppercase; font-size: 10px; padding: 8px; text-align: left; }
        .items-table td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; vertical-align: top; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }

        .totals-section { margin-top: 20px; width: 45%; float: right; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #e2e8f0; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>

    @php
        $isSpecialBilling = ($invoice->billing_source ?? 'matter') === 'special_billing';

        $clientName = $invoice->client->name
            ?? $invoice->matter?->client?->name
            ?? 'Valued Client';

        $matterTitle = $invoice->matter?->name
            ?? ($isSpecialBilling
                ? 'Special Retainer / Recurring Billing' . ($invoice->period_key ? " ({$invoice->period_key})" : '')
                : 'N/A');

        $currency = $invoice->currency ?? $invoice->matter?->currency ?? 'PHP';
        $symbol = $currency === 'PHP' ? '₱' : '$';

        // Matter Billing Type Flags
        $billingType = strtolower($invoice->matter?->billing_type ?? 'hourly');
        $isHourly = ! $isSpecialBilling && $billingType === 'hourly';
        $isFixedRate = ! $isSpecialBilling && $billingType === 'fixed_rate';
        $isContingency = ! $isSpecialBilling && $billingType === 'contingency';

        // Tier Calculations (for Hourly Matter Invoices)
        $billingPercentage = (float) ($invoice->billing_percentage ?? 100.00);
        $isDiscounted = $isHourly && $billingPercentage < 100;
        $isPremium = $isHourly && $billingPercentage > 100;

        $tierName = match ($invoice->billing_tier ?? 'standard') {
            'priority'  => 'Priority',
            'select'    => 'Select',
            'preferred' => 'Preferred',
            default     => 'Standard',
        };

        // Totals Calculations
        if ($isSpecialBilling) {
            $ourFees = (float) $invoice->items->sum('amount');
            $vat = (float) $invoice->items->sum('vat_amount');
        } else {
            $ourFees = $invoice->total_amount / 1.12;
            $vat = $ourFees * 0.12;
        }

        $grossStandardFees = $isHourly
            ? $invoice->timeEntries->sum(fn ($e) => round((float) ($e->approved_hours ?? $e->hours) * (float) $e->hourly_rate, 2))
            : $ourFees;
        $discountAmount = max(0, $grossStandardFees - $ourFees);
    @endphp

    <!-- Firm Letterhead & Invoice Header -->
    <table class="header-table">
        <tr>
            <td>
                <div class="firm-name">Ortega, Bacorro, Odulio, Calma &amp; Carbonell</div>
                <div class="firm-address">
                    Attorneys-at-Law<br>
                    5th and 6th Floors ALPAP 1 Bldg.<br>
                    140 L.P. Leviste St., Salcedo Village<br>
                    Makati City<br>
                    Email: law@ocbocc.com
                </div>
            </td>
            <td>
                <div class="invoice-title">{{ $isSpecialBilling ? 'STATEMENT OF ACCOUNT' : 'INVOICE' }}</div>
                <div class="invoice-meta text-right">
                    <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Date:</strong> {{ $invoice->issue_date->format('F d, Y') }}<br>
                    <strong>Due Date:</strong> {{ $invoice->due_date->format('F d, Y') }}<br>
                    <strong>Currency:</strong> {{ $currency }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Client and Matter Particulars Box -->
    <div class="box">
        <table class="details-table">
            <tr>
                <td class="bold" style="width: 15%;">FOR:</td>
                <td style="width: 35%;">{{ $clientName }}</td>
                <td class="bold" style="width: 15%;">{{ $isSpecialBilling ? 'ACCOUNT / FILE:' : 'MATTER FILE:' }}</td>
                <td style="width: 35%;">{{ $matterTitle }}</td>
            </tr>
            <tr>
                <td class="bold">ATTENTION:</td>
                <td>Client Billing Department</td>
                <td class="bold">{{ $isSpecialBilling ? 'BILLING PERIOD:' : 'AUTHORITY:' }}</td>
                <td>{{ $isSpecialBilling ? ($invoice->period_key ?? 'Recurring') : ($invoice->matter?->authority ?? 'N/A') }}</td>
            </tr>
        </table>
    </div>

    <!-- Billing Terms Summary (For Fixed Rate & Contingency) -->
    @if($isFixedRate || $isContingency)
        <div class="box">
            <h4 style="margin-top: 0; margin-bottom: 5px; text-transform: uppercase; font-size: 11px; color: #475569;">Billing Terms</h4>
            <table class="details-table">
                @if($isFixedRate)
                    <tr>
                        <td class="bold" style="width: 20%;">BILLING TYPE:</td>
                        <td style="width: 30%;">Fixed Rate</td>
                        <td class="bold" style="width: 20%;">FIXED FEE AMOUNT:</td>
                        <td style="width: 30%;">{{ $symbol }}{{ number_format($invoice->matter->fixed_fee_amount, 2) }}</td>
                    </tr>
                @endif

                @if($isContingency)
                    <tr>
                        <td class="bold" style="width: 20%;">BILLING TYPE:</td>
                        <td style="width: 30%;">Contingency</td>
                        <td class="bold" style="width: 20%;">CONTINGENCY RATE:</td>
                        <td style="width: 30%;">{{ $invoice->matter->contingency_percentage }}%</td>
                    </tr>
                    <tr>
                        <td class="bold">SETTLEMENT / JUDGMENT:</td>
                        <td colspan="3">{{ $symbol }}{{ number_format($invoice->matter->settlement_judgment, 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>
    @endif

    @if($isSpecialBilling)
        <!-- Special Billing Schedule of Fees -->
        <h4 style="margin-top: 30px; margin-bottom: 5px; text-transform: uppercase; font-size: 11px; color: #475569;">
            Schedule of Fees ({{ $currency }})
        </h4>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 55%;">Particulars / Description</th>
                    <th class="text-center" style="width: 15%;">Tax Status</th>
                    <th class="text-right" style="width: 15%;">Fee Amount</th>
                    <th class="text-right" style="width: 15%;">12% VAT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="bold">{{ $item->description }}</td>
                        <td class="text-center">{{ (float) $item->vat_amount > 0 ? 'VATable' : 'Non-VAT' }}</td>
                        <td class="text-right bold">{{ $symbol }}{{ number_format($item->amount, 2) }}</td>
                        <td class="text-right">{{ $symbol }}{{ number_format($item->vat_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Regular Matter Professional Services Itemized Breakdown -->
        <h4 style="margin-top: 30px; margin-bottom: 5px; text-transform: uppercase; font-size: 11px; color: #475569;">
            Professional Services Rendered ({{ $currency }})
        </h4>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 15%;">Timekeeper</th>
                    <th style="width: {{ $isHourly ? '43%' : '63%' }};">Description of Services</th>
                    <th class="text-center" style="width: 10%;">Hours</th>
                    @if($isHourly)
                        <th class="text-right" style="width: 10%;">Rate</th>
                        <th class="text-right" style="width: 10%;">Amount</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->timeEntries as $entry)
                    <tr>
                        <td>{{ $entry->start_time->format('m/d/Y') }}</td>
                        <td class="bold">{{ $entry->user->name ?? 'Lawyer' }}</td>
                        <td>{{ $entry->formatted_description }}</td>
                        <td class="text-center">{{ number_format($entry->approved_hours ?? $entry->hours, 2) }}</td>
                        @if($isHourly)
                            @php
                                $effectiveHrs = (float) ($entry->approved_hours ?? $entry->hours);
                                $displayRate = $isPremium ? ((float) $entry->hourly_rate * ($billingPercentage / 100)) : (float) $entry->hourly_rate;
                                $displayAmt = $isPremium ? round($effectiveHrs * $displayRate, 2) : (float) $entry->amount;
                            @endphp
                            <td class="text-right">{{ $symbol }}{{ number_format($displayRate, 2) }}</td>
                            <td class="text-right bold">{{ $symbol }}{{ number_format($displayAmt, 2) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Totals Calculations -->
    <div class="totals-section">
        <table class="totals-table">
            @if($isDiscounted)
                <tr>
                    <td><strong>Standard Fees:</strong></td>
                    <td class="text-right">{{ $symbol }}{{ number_format($grossStandardFees, 2) }}</td>
                </tr>
                <tr>
                    <td style="color: #047857;"><strong>Less: {{ $tierName }} Adjustment ({{ number_format(100 - $billingPercentage, 0) }}%):</strong></td>
                    <td class="text-right" style="color: #047857;">({{ $symbol }}{{ number_format($discountAmount, 2) }})</td>
                </tr>
            @endif
            <tr>
                <td><strong>{{ $isDiscounted ? 'Net Professional Fees:' : 'Our Fees:' }}</strong></td>
                <td class="text-right bold">{{ $symbol }}{{ number_format($ourFees, 2) }}</td>
            </tr>
            <tr>
                <td><strong>+ 12% VAT:</strong></td>
                <td class="text-right">{{ $symbol }}{{ number_format($vat, 2) }}</td>
            </tr>
            <tr style="background-color: #f1f5f9;">
                <td><strong>Total ({{ $currency }}):</strong></td>
                <td class="text-right bold" style="font-size: 13px; color: #0f172a;">{{ $symbol }}{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Remarks / Payment Advice -->
    @if($invoice->remarks)
        <div style="clear: both; margin-top: 40px; font-size: 11px;">
            <strong>Remarks / Payment Instructions:</strong><br>
            {{ $invoice->remarks }}
        </div>
    @endif

    <div class="footer">
        Please make checks payable to <strong>Ortega, Bacorro, Odulio, Calma &amp; Carbonell</strong>. Thank you for your continued confidence and patronage.
    </div>

</body>
</html>