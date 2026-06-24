@props(['doc' => null, 'labeled' => false])

@if($doc ?? null)
    <div class="{{ $labeled ? 'd-flex flex-wrap' : 'btn-group btn-group-sm' }}" @if($labeled) style="gap:0.35rem;" @endif>
        <a href="{{ route('phase.doc.preview', $doc->id) }}"
           target="_blank"
           class="btn {{ $labeled ? 'btn-sm btn-outline-info' : 'btn-outline-info' }}"
           title="View in browser">
            <i class="fas fa-eye{{ $labeled ? ' mr-1' : '' }}"></i>@if($labeled) View @endif
        </a>
        <a href="{{ route('phase.doc.serve', ['id' => $doc->id, 'action' => 'download']) }}"
           class="btn {{ $labeled ? 'btn-sm btn-outline-primary' : 'btn-outline-primary' }}"
           title="Download">
            <i class="fas fa-download{{ $labeled ? ' mr-1' : '' }}"></i>@if($labeled) Download @endif
        </a>
    </div>
@endif
