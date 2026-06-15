<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn mr-2']) }}>
    {{ $slot }}
</button>
