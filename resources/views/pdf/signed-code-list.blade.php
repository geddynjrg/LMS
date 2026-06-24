<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Approved Codelist</title>
    <style>
        body { font-family: Tahoma, DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 18px; }
        .header-table { width: 100%; border: none; margin-bottom: 8px; }
        .header-table td { border: none; vertical-align: middle; padding: 0; }
        .header-logo { width: 90px; }
        .header-logo img { height: 88px; width: auto; }
        .header-text { text-align: center; }
        .header-text h1 { font-size: 14px; margin: 0 0 2px; font-weight: bold; }
        .header-text h2 { font-size: 12px; margin: 0 0 6px; font-weight: bold; }
        .header-text div { font-size: 12px; font-weight: bold; }
        .meta { text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        table.data-table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px 3px; vertical-align: top; }
        th { background: #e9e9e9; font-weight: bold; text-align: center; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .totals td { font-weight: bold; background: #f5f5f5; }
        .summary-wrap { margin-top: 12px; width: 42%; margin-left: auto; border-collapse: collapse; }
        .summary-wrap td { border: 1px solid #444; padding: 3px 5px; font-size: 12px; }
        .summary-label { width: 65%; }
        .summary-value { text-align: right; width: 35%; }
        .summary-bold td { font-weight: bold; }
        .summary-spacer td { border: none !important; height: 8px; padding: 0; }
        .summary-note td { font-weight: bold; text-align: center; }
        .footer-wrap { margin-top: 18px; width: 100%; }
        .footer-wrap td { border: none; vertical-align: bottom; padding: 0; }
        .signature-box { text-align: left; width: 240px; }
        .signature-box img { max-height: 70px; max-width: 220px; }
        .signature-label { font-size: 8px; color: #555; margin-top: 4px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($logoData))
                    <img src="data:image/png;base64,{{ $logoData }}" alt="Uwezo Fund Logo">
                @endif
            </td>
            <td class="header-text">
                <h1>UWEZO FUND</h1>
                <h2>APPROVED CODE LIST {{ strtoupper($phase->phase) }}</h2>
                <div>Republic of Kenya</div>
                <div>Uwezo Fund Oversight Board</div>
            </td>
            <td class="header-logo"></td>
        </tr>
    </table>

    <div class="meta">
        Name of Constituency: {{ strtoupper($constituencyName) }} |
        IEBC Code: {{ $constCode }} |
        County: {{ strtoupper($countyName) }}<br>
        For the Month Ended: {{ now()->format('d F Y') }}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>S/No.</th>
                <th>CODE</th>
                <th>GROUP NAME</th>
                <th>GFS CODE</th>
                <th>APPLIED</th>
                <th>CURRENT GROUP ACTIVITY</th>
                <th>PREVIOUS LOAN AMMOUNT</th>
                <th>LOAN LEVEL</th>
                <th>RECOMMENDED</th>
                <th>CURRENT STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="center">{{ $row['sno'] }}</td>
                    <td class="center">{{ $row['code'] }}</td>
                    <td>{{ $row['group_name'] }}</td>
                    <td>{{ $row['gfs_code'] }}</td>
                    <td class="num">{{ number_format($row['applied'], 0) }}</td>
                    <td>{{ $row['activity'] }}</td>
                    <td class="num">{{ number_format($row['previous_loan'], 0) }}</td>
                    <td class="center">{{ $row['loan_level'] }}</td>
                    <td class="num">{{ number_format($row['recommended'], 0) }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="4" style="text-align:right;">TOTAL</td>
                <td class="num">{{ number_format($totals['applied'], 0) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="num">{{ number_format($totals['recommended'], 0) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @if(!empty($financialSummary))
        <table class="summary-wrap">
            @foreach($financialSummary['rows'] as $summaryRow)
                @if(!empty($summaryRow['spacer']))
                    <tr class="summary-spacer"><td colspan="2"></td></tr>
                @elseif(!empty($summaryRow['center']))
                    <tr class="summary-note">
                        <td colspan="2">{{ $summaryRow['label'] }}</td>
                    </tr>
                @else
                    <tr class="{{ !empty($summaryRow['bold']) ? 'summary-bold' : '' }}">
                        <td class="summary-label">{{ $summaryRow['label'] }}</td>
                        <td class="summary-value">
                            @if(array_key_exists('amount', $summaryRow) && $summaryRow['amount'] !== null)
                                {{ number_format((float) $summaryRow['amount'], 0) }}
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
    @endif

    <table class="footer-wrap">
        <tr>
            <td style="width: 40%;">
                <div class="signature-box">
                    <img src="data:{{ $signatureMime }};base64,{{ $signatureData }}" alt="Signature">
                    <div class="signature-label">
                        Signed by {{ $signedBy }}<br>
                        {{ $signedAt?->format('d M Y H:i') }}
                    </div>
                </div>
            </td>
            <td style="width: 60%;"></td>
        </tr>
    </table>
</body>
</html>
