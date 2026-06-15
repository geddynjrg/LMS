<div {{ $attributes->merge(['class' => 'md:grid md:grid-cols-3 md:gap-6']) }}>
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="mt-2 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 sm:p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    {{ $content }}
                </div>
            </div>
        </div>
    </div>
</div>
