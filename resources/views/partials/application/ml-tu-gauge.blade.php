{{--
  Expected: $mlIntake array with tu_like_score, tu_like_color, fill_pct (optional), tu_palette (optional)
  Optional: $mlEarly, $compact (bool), $showEarlywarn (bool, default true)
--}}
@php
    $score = (int) ($mlIntake['tu_like_score'] ?? 0);
    $fillPct = (float) ($mlIntake['fill_pct'] ?? max(0, min(100, ($score - 300) / 550 * 100)));
    $color = $mlIntake['tu_like_color'] ?? '#f4a27a';
    $palette = $mlIntake['tu_palette'] ?? ['#7a1f1f','#c62828','#e65100','#f4a27a','#f9c74f','#fde68a','#7dd3fc','#38bdf8','#86efac','#15803d'];
    $size = !empty($compact) ? 120 : 160;
    $stroke = !empty($compact) ? 10 : 12;
    $r = ($size / 2) - ($stroke / 2) - 2;
    $c = 2 * pi() * $r;
    $dash = ($fillPct / 100) * $c;
    $showEw = $showEarlywarn ?? true;
@endphp
<div class="ml-tu-gauge text-center {{ !empty($compact) ? 'ml-tu-gauge--compact' : '' }}">
    <div class="d-inline-block position-relative mb-2" style="width: {{ $size }}px; height: {{ $size }}px;">
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" aria-label="ML score {{ $score }}">
            <circle
                cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $r }}"
                fill="none" stroke="#e9ecef" stroke-width="{{ $stroke }}"
            />
            <circle
                cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $r }}"
                fill="none" stroke="{{ $color }}" stroke-width="{{ $stroke }}"
                stroke-linecap="round"
                stroke-dasharray="{{ $dash }} {{ $c }}"
                transform="rotate(-90 {{ $size / 2 }} {{ $size / 2 }})"
            />
        </svg>
        <div class="position-absolute d-flex align-items-center justify-content-center"
             style="inset: 0;">
            <span class="font-weight-bold font-mono mb-0"
                  style="font-size: {{ !empty($compact) ? '1.5rem' : '2rem' }}; color: {{ $color }}; line-height: 1;">
                {{ $score ?: '—' }}
            </span>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-center px-2 mb-1" style="gap: 6px; max-width: {{ max($size + 40, 220) }}px; margin: 0 auto;">
        <span class="text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.04em;">Poor</span>
        <div class="d-flex flex-grow-1" style="gap: 2px; height: 10px;">
            @foreach($palette as $swatch)
                <div class="flex-grow-1 rounded-sm" style="background: {{ $swatch }}; min-width: 8px;"></div>
            @endforeach
        </div>
        <span class="text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.04em;">Good</span>
    </div>

    @if($showEw)
        @if(is_array($mlEarly ?? null) && !empty($mlEarly['applicable']))
            <div class="small text-muted mt-2">
                Early-warning:
                <span class="font-mono font-weight-bold" style="color: {{ $mlEarly['tu_like_color'] ?? '#333' }};">
                    {{ $mlEarly['tu_like_score'] ?? '—' }}
                    ({{ number_format($mlEarly['probability_pct'] ?? 0, 1) }}%)
                </span>
            </div>
        @elseif(($showEarlywarnLine ?? true) === true)
            <div class="small text-muted mt-2">Early-warning: not available until disbursement</div>
        @endif
    @endif

    <div class="small text-muted mt-2" style="font-size: 0.75rem;">Not a CRB report</div>
</div>
