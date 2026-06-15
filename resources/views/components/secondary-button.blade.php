<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn mr-2 uppercase tracking-widest shadow-sm hover:bg-black-50 focus:outline-none focus:ring-2 focus:ring-indigo focus:ring-offset-2 dark:focus:ring-offset-gray disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>