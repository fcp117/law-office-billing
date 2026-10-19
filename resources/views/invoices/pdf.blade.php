<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Account - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Dejavu Sans', Helvetica, Arial, sans-serif; color: #333; font-size: 12px; line-height: 1.4; margin: 0; padding: 0; }
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
        
        .totals-section { margin-top: 20px; width: 40%; float: right; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #e2e8f0; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>

    <!-- Firm Letterhead & Invoice Header -->
    <table class="header-table">
        <tr>
            <td>
                <div class="firm-name">Ortega, Bacorro, Odulio, Calma & Carbonell</div>
                <div class="firm-address">
                    Attorneys-at-Law<br>
                    5th and 6th Floors ALPAP 1 Bldg.<br>
                    140 L.P. Leviste St., Salcedo Village<br>
                    Makati City<br>
                    Email: law@ocbocc.com
                </div>
            </td>
            <td>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta text-right">
                    <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Date:</strong> {{ $invoice->issue_date->format('F d, Y') }}<br>
                    <strong>Due Date:</strong> {{ $invoice->due_date->format('F d, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Client and Matter Particulars Box -->
    <div class="box">
        <table class="details-table">
            <tr>
                <td class="bold" style="width: 15%;">FOR:</td>
                <td style="width: 35%;">{{ $invoice->matter->client->name ?? 'Valued Client' }}</td>
                <td class="bold" style="width: 15%;">MATTER FILE:</td>
                <td style="width: 35%;">{{ $invoice->matter->name }}</td>
            </tr>
            <tr>
                <td class="bold">ATTENTION:</td>
                <td>Client Billing Department</td>
                <td class="bold">AUTHORITY:</td>
                <td>{{ $invoice->matter->authority ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Professional Services Itemized Breakdown -->
    <h4 style="margin-top: 30px; margin-bottom: 5px; text-transform: uppercase; font-size: 11px; color: #475569;">Professional Services Rendered</h4>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 15%;">Timekeeper</th>
                <th style="width: 43%;">Description of Services</th>
                <th class="text-center" style="width: 10%;">Hours</th>
                <th class="text-right" style="width: 10%;">Rate</th>
                <th class="text-right" style="width: 10%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->timeEntries as $entry)
                <tr>
                    <td>{{ $entry->start_time->format('m/d/Y') }}</td>
                    <td class="bold">{{ $entry->user->name ?? 'Lawyer' }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="text-center">{{ number_format($entry->hours, 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format($entry->hourly_rate, 2) }}</td>
                    <td class="text-right bold">&#8369;{{ number_format($entry->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Calculations -->

    @php
    $ourFees = $invoice->total_amount / 1.12;
    $vat = $ourFees * 0.12;
    @endphp
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td><strong>Our Fees:</strong></td>
                <td class="text-right bold">&#8369;{{ number_format($ourFees, 2) }}</td>
            </tr>
            <tr>
                <td><strong>+ 12% VAT:</strong></td>
                <td class="text-right">&#8369;{{number_format($vat, 2)}}</td>
            </tr>
            <tr style="background-color: #f1f5f9;">
                <td><strong>Total:</strong></td>
                <td class="text-right bold" style="font-size: 13px; color: #0f172a;">&#8369;{{ number_format($invoice->total_amount, 2) }}</td>
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
        Please make checks payable to <strong>Ortega, Bacorro, Odulio, Calma & Carbonell</strong>. Thank you for your continued confidence and patronage.
    </div>

</body>
</html>