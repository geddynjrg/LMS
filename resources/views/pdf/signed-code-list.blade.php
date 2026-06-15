<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Signed Code List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 18px; }
        .header { text-align: center; margin-bottom: 8px; }
        .header h1 { font-size: 14px; margin: 0 0 2px; }
        .header h2 { font-size: 12px; margin: 0 0 6px; }
        .meta { text-align: center; font-size: 9px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px 3px; vertical-align: top; }
        th { background: #e9e9e9; font-weight: bold; text-align: center; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        .totals td { font-weight: bold; background: #f5f5f5; }
        .footer-wrap { margin-top: 14px; width: 100%; }
        .footer-wrap td { border: none; vertical-align: bottom; padding: 0; }
        .signature-box { text-align: right; width: 220px; margin-left: auto; }
        .signature-box img { max-height: 70px; max-width: 200px; }
        .signature-label { font-size: 8px; color: #555; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UWEZO FUND</h1>
        <h2>CODE LIST {{ strtoupper($phase->phase) }}</h2>
        <div>Republic of Kenya</div>
        <div>Uwezo Fund Oversight Board</div>
    </div>

    <div class="meta">
        Name of Constituency: {{ strtoupper($constituencyName) }} |
        IEBC Code: {{ $constCode }} |
        County: {{ strtoupper($countyName) }}<br>
        For the Month Ended: {{ now()->format('d F Y') }}
    </div>

    <table>
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
                    <td class="num">{{ number_format($row['applied']) }}</td>
                    <td>{{ $row['activity'] }}</td>
                    <td class="num">{{ number_format($row['previous_loan']) }}</td>
                    <td class="center">{{ $row['loan_level'] }}</td>
                    <td class="num">{{ number_format($row['recommended']) }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="4" style="text-align:right;">TOTAL</td>
                <td class="num">{{ number_format($totals['applied']) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="num">{{ number_format($totals['recommended']) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="footer-wrap">
        <tr>
            <td style="width: 65%;"></td>
            <td style="width: 35%;">
                <div class="signature-box">
                    <img src="data:{{ $signatureMime }};base64,{{ $signatureData }}" alt="Signature">
                    <div class="signature-label">
                        Signed by {{ $signedBy }}<br>
                        {{ $signedAt?->format('d M Y H:i') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
