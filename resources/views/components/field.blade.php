@props(['name', 'label', 'type' => 'text', 'required' => false, 'help' => null, 'value' => null, 'autocomplete' => null, 'rows' => 5])
@php $id = 'f-'.str_replace(['[', ']', '.'], '-', $name); $err = $errors->first($name); @endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">{{ $label }}@if ($required) <span class="text-ember" aria-hidden="true">*</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="form-input" @required($required) @if ($err) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif {{ $attributes->except('class') }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" class="form-input" @required($required) @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if ($err) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif {{ $attributes->except('class') }}>
    @endif
    @if ($help)<p class="form-help">{{ $help }}</p>@endif
    @if ($err)<p id="{{ $id }}-error" class="form-error">{{ $err }}</p>@endif
</div>
