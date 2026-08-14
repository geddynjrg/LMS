{{-- Assistive officer review checklist (rule-based, not auto-decision) --}}
@php
    $phaseModel = $phase ?? $loanPhase ?? null;
@endphp
@if($phaseModel)
<div class="card border shadow-sm mb-3" id="review-checklist-draft-card"
     data-phase-id="{{ $phaseModel->id }}"
     data-load-url="{{ route('review-checklist.show', $phaseModel) }}"
     data-save-url="{{ route('review-checklist.save', $phaseModel) }}">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 font-weight-bold text-navy">
            <i class="fas fa-tasks text-teal mr-1"></i>
            Officer review checklist
            <span class="badge badge-light border ml-1">Assistive</span>
        </h6>
        <small class="text-muted">Draft ticks are saved for this phase status. Not an automated approval.</small>
    </div>
    <div class="card-body py-2">
        <div id="review-checklist-loading" class="text-muted small">Loading checklist…</div>
        <form id="review-checklist-form" class="d-none">
            @csrf
            <div id="review-checklist-items" class="mb-2"></div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Save checklist progress</button>
            <span id="review-checklist-status" class="small text-muted ml-2"></span>
        </form>
    </div>
</div>
<script>
(function () {
    var card = document.getElementById('review-checklist-draft-card');
    if (!card) return;
    var loadUrl = card.getAttribute('data-load-url');
    var saveUrl = card.getAttribute('data-save-url');
    var loading = document.getElementById('review-checklist-loading');
    var form = document.getElementById('review-checklist-form');
    var itemsEl = document.getElementById('review-checklist-items');
    var statusEl = document.getElementById('review-checklist-status');
    var token = form.querySelector('input[name=_token]')?.value;

    fetch(loadUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            loading.classList.add('d-none');
            form.classList.remove('d-none');
            var checklist = data.checklist || [];
            var progress = data.progress || {};
            itemsEl.innerHTML = '';
            checklist.forEach(function (item) {
                var id = item.id;
                var wrap = document.createElement('label');
                wrap.className = 'd-flex align-items-start mb-2 small';
                wrap.innerHTML = '<input type="checkbox" class="mr-2 mt-1" data-item-id="' + id + '"' +
                    (progress[id] ? ' checked' : '') + '> <span>' +
                    (item.required ? '<strong>*</strong> ' : '') +
                    (item.label || id) + '</span>';
                itemsEl.appendChild(wrap);
            });
        })
        .catch(function () {
            loading.textContent = 'Checklist unavailable.';
        });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var progress = {};
        itemsEl.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
            progress[cb.getAttribute('data-item-id')] = cb.checked;
        });
        statusEl.textContent = 'Saving…';
        fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token || ''
            },
            body: JSON.stringify({ progress: progress })
        })
            .then(function (r) { return r.json(); })
            .then(function () { statusEl.textContent = 'Saved.'; })
            .catch(function () { statusEl.textContent = 'Save failed.'; });
    });
})();
</script>
@endif
