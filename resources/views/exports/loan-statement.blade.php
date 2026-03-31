<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loan Statement - {{ $loan->loan_number ?? 'N/A' }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 12px; line-height: 1.5; }
        .header-table { width: 100%; border-bottom: 2px solid #444; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; text-transform: uppercase; color: #1a1a1a; }
        .meta-table { width: 100%; margin-bottom: 30px; }
        .meta-label { color: #777; text-transform: uppercase; font-size: 10px; font-weight: bold; }
        .meta-value { font-size: 13px; font-weight: bold; }

        .summary-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-bottom: 30px; }
        .summary-table { width: 100%; }
        .summary-item { text-align: center; width: 33%; }

        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th { background-color: #f2f2f2; color: #444; text-align: left; padding: 8px; border-bottom: 1px solid #ccc; text-transform: uppercase; font-size: 10px; }
        .table td { padding: 8px; border-bottom: 1px solid #eee; }

        .text-right { text-align: right; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 9px; color: #777; text-align: center; border-top: 1px solid #eee; padding-top: 5px; }

        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; color: white; }
        .bg-success { background-color: #28a745; }
        .bg-danger { background-color: #dc3545; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <div class="title">Loan Repayment Statement</div>
                <div style="color: #666;">Generated on: {{ date('d M, Y H:i') }}</div>
            </td>
            <td class="text-right">
                <div style="font-size: 16px; font-weight: bold;">{{ config('app.name') }}</div>
                <div>Repayment Summary</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td width="50%">
                <div class="meta-label">Group Name</div>
                <div class="meta-value">{{ $loan->group->name ?? 'N/A' }}</div>
            </td>
            <td width="50%">
                <div class="meta-label">Loan Reference</div>
                <div class="meta-value">#{{ $loan->loan_number ?? $loan->id }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 10px;">
                <div class="meta-label">Disbursement Date</div>
                <div class="meta-value">{{ date('d F, Y', strtotime($loan->disbursement_date)) }}</div>
            </td>
            <td style="padding-top: 10px;">
                <div class="meta-label">Loan Phase</div>
                <div class="meta-value">{{ $loan->loanPhase->phase ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="summary-item">
                    <div class="meta-label">Principal Issued</div>
                    <div style="font-size: 16px; font-weight: bold;">KES {{ number_format($loan->amount_issued, 2) }}</div>
                </td>
                <td class="summary-item" style="border-left: 1px solid #ccc; border-right: 1px solid #ccc;">
                    <div class="meta-label">Total Repaid</div>
                    <div style="font-size: 16px; font-weight: bold;" class="text-success">KES {{ number_format($loan->amount_repaid, 2) }}</div>
                </td>
                <td class="summary-item">
                    <div class="meta-label">Outstanding Balance</div>
                    <div style="font-size: 16px; font-weight: bold;" class="{{ ($loan->amount_issued - $loan->amount_repaid) > 0 ? 'text-danger' : '' }}">
                        KES {{ number_format($loan->amount_issued - $loan->amount_repaid, 2) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style="font-weight: bold; margin-bottom: 10px; text-transform: uppercase; font-size: 11px;">Repayment Schedule & Receipts</div>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Receipt Number</th>
                <th>Transaction Date</th>
                <th>Reference / Method</th>
                <th class="text-right">Amount (KES)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loan->loanReceipts as $index => $receipt)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: bold;">{{ $receipt->receipt_number }}</td>
                    <td>{{ date('d-m-Y', strtotime($receipt->transaction_date)) }}</td>
                    <td>{{ $receipt->reference_number }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($receipt->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #999;">No repayment transactions found for this loan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right" style="font-weight: bold; padding-top: 15px;">TOTAL REPAID:</td>
                <td class="text-right" style="font-weight: bold; padding-top: 15px; font-size: 14px; color: #28a745;">
                    KES {{ number_format($loan->amount_repaid, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        This is a system-generated statement. No signature is required. <br>
        &copy; {{ date('Y') }} {{ config('app.name') }}. All Rights Reserved.
    </div>

</body>
</html>
