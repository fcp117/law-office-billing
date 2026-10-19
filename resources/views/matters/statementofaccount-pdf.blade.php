<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statement of Account - {{ $matter->name }}</title>
    <style>
        body {
            font-family: 'Dejavu Sans', Helvetica, Arial, sans-serif;
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #0f172a;
            margin: 0 0 5px 0;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }
        .client-info {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .client-info h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #0f172a;
        }
        .client-info p {
            margin: 0;
            color: #475569;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 13px;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-green {
            color: #16a34a;
        }
        .summary-box {
            float: right;
            width: 300px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            text-align: right;
            page-break-inside: avoid;
        }
        .summary-box h3 {
            margin: 0 0 5px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.05em;
        }
        .summary-box p {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h1>Statement of Account</h1>
            <p>Generated on {{ date('F j, Y') }}</p>
        </div>

        <!-- Client & Matter Info -->
        <div class="client-info">
            <h3>Client: {{ $matter->client->name ?? 'N/A' }}</h3>
            <p>Matter File: {{ $matter->name }} &nbsp;|&nbsp; Authority: {{ $matter->authority ?? 'N/A' }}</p>
        </div>

        <!-- Invoices Table -->
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date Issued</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Payment</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matter->invoices as $inv)
                    <tr>
                        <td class="font-bold">{{ $inv->invoice_number }}</td>
                        <!-- Fixed: Using Carbon's format() instead of JS toLocaleDateString() -->
                        <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('M d, Y') }}</td>
                        <td class="text-right">₱{{ number_format($inv->total_amount, 2) }}</td>
                        <td class="text-right text-green">₱{{ number_format($inv->total_payments, 2) }}</td>
                        <td class="text-right font-bold">₱{{ number_format($inv->balance_due, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b;">No invoices found for this matter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="clearfix">
            <div class="summary-box">
                <h3>Total Unpaid Amount</h3>
                <p>₱{{ number_format($matter->total_unpaid_amount ?? 0, 2) }}</p>
            </div>
        </div>
    </div>

</body>
</html>