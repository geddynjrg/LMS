@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
   
    <div class="text-lg font-medium text-gray">
        {{ $title }}
    </div>

    <div class="mt-4 text-sm text-gray">
        {{ $content }}
    </div>


    <div class="float-right text-end">
        {{ $footer }}
    </div>
</x-modal>
 