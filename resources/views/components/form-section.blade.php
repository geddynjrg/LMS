@props(['submit'])

<div {{ $attributes->merge(['class' => 'md:grid md:grid-cols-3 md:gap-6']) }}>
    <div class="card card-primary card-outline">
        <div class="card-body">
            <form wire:submit="{{ $submit }}">
                {{ $form }}
        
                @if (isset($actions))
                    <div class="btn-group float-right " role="group">
                        {{ $actions }}
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
