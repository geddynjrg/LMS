@props([
    'model',
    'label',
    'options' => [],
    'placeholder' => 'Select an option',
    'optionValue' => 'id',
    'optionLabel' => 'phase',
    'default' => null,
    'defaultLabel' => ''
])

<div class="form-group" wire:ignore>
    <label>{{ $label }}</label>
    <select
        id="select2-{{ $model }}"
        wire:model="{{ $model }}"
        {{ $attributes->merge(['class' => 'form-control select2']) }}
    >
        <option value="">{{ $placeholder }}</option>

        @if($default)
            <option value="{{ $default }}" {{ $this->$model == $default ? 'selected' : '' }}>
                {{ $defaultLabel }}
            </option>
        @endif

        @foreach($options as $key => $item)
            @php
                $value = is_object($item) ? $item->$optionValue : $key;
                $display = is_object($item) ? $item->$optionLabel : $item;
                $extraStatus = is_object($item) && isset($item->status) ? $item->status : null;

                $isSelected = ($this->$model == $value) ? 'selected' : '';
            @endphp

            <option value="{{ $value }}" {{ $isSelected }}>
                {{ ucfirst(str_replace('_', ' ', $display)) }}
                @if($extraStatus) ({{ ucfirst($extraStatus) }}) @endif
            </option>
        @endforeach
    </select>
</div>
