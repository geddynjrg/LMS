@props(['for'])

@error($for)
    <p {{ $attributes->merge(['class' => 'text-sm text-red text-bold']) }}>{{ $message }}</p>
@enderror
