@php
    $name = $name ?? '';
    $label = $label ?? ucfirst($name);
    $type = $type ?? 'text';
    $value = $value ?? '';
    $required = $required ?? false;
    $options = $options ?? [];
    $selected = $selected ?? '';
    $errors = session('errors');
    $error = $errors ? $errors->first($name) : null;
    $displayValue = old($name, $value);
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    
    @if($type === 'select')
        <select name="{{ $name }}" id="{{ $name }}" class="form-select @if($error) is-invalid @endif">
            <option value="">Seleccionar...</option>
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" {{ ($selected == $optionValue) ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea name="{{ $name }}" id="{{ $name }}" class="form-control @if($error) is-invalid @endif">{{ $displayValue }}</textarea>
    @else
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            value="{{ $displayValue }}"
            class="form-control @if($error) is-invalid @endif"
        >
    @endif
    
    @if($error)
        <div class="invalid-feedback">
            {{ $error }}
        </div>
    @endif
</div>