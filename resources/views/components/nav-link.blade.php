@props(['route', 'params' => [], 'icon'])

@php

    $isActive = request()->routeIs($route) == $route;
@endphp

<li class="nav-item">
    <a href="{{ route($route, $params) }}"
       {{ $attributes->merge(['class' => 'nav-link ' . ($isActive ? 'active' : '')]) }}
       wire:navigate>
        <i class="{{ $icon }} nav-icon"></i>
        <p>
            {{ $slot }}
            {{-- @if($badge > 0)
                <span class="badge {{ $badgeClass }} right">{{ $badge }}</span>
            @endif --}}

            @if(isset($badgeSlot))
                {{ $badgeSlot }}
            @endif
        </p>
    </a>
</li>
