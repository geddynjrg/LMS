<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Letter of Authority</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 36px; line-height: 1.5; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 18px; }
        .meta { margin-bottom: 16px; }
        .signature { margin-top: 36px; text-align: right; }
        .signature img { max-height: 80px; max-width: 220px; }
    </style>
</head>
<body>
    <h1>Letter of Authority to Disburse Funds</h1>

    <div class="meta">
        <strong>Constituency:</strong> {{ $constituencyName }} ({{ $constCode }})<br>
        <strong>County:</strong> {{ $countyName }}<br>
        <strong>Phase:</strong> {{ $phase->phase }} ({{ $phase->phase_ref }})<br>
        <strong>Total approved amount:</strong> KES {{ number_format($requestedAmount, 2) }}<br>
        <strong>Available constituency bank balance:</strong> KES {{ number_format($availableBalance, 2) }}<br>
        <strong>Shortfall requiring HQ transfer:</strong> KES {{ number_format(max(0, $requestedAmount - $availableBalance), 2) }}
    </div>

    <p>
        This letter authorizes the transfer of funds from the constituency bank account to the headquarters account
        to facilitate disbursement of the approved groups listed in the attached phase, where the approved amount
        exceeds the available constituency bank balance.
    </p>

    <div class="signature">
        <img src="data:{{ $signatureMime }};base64,{{ $signatureData }}" alt="Signature"><br>
        <strong>{{ $signedBy }}</strong><br>
        Head of Secretariat<br>
        {{ $signedAt?->format('d M Y H:i') }}
    </div>
</body>
</html>
