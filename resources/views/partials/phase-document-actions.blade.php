@if($doc ?? null)
    <div class="btn-group btn-group-sm">
        @if($doc->can_view_inline)
            <a href="{{ route('phase.doc.serve', ['id' => $doc->id, 'action' => 'view']) }}"
               target="_blank"
               class="btn btn-outline-info"
               title="View in browser">
                <i class="fas fa-eye"></i>
            </a>
        @endif
        <a href="{{ route('phase.doc.serve', ['id' => $doc->id, 'action' => 'download']) }}"
           class="btn btn-outline-primary"
           title="Download">
            <i class="fas fa-download"></i>
        </a>
    </div>
@endif
