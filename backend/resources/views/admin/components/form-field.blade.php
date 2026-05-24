@php
    $name = $name ?? '';
    $label = $label ?? ucfirst($name);
    $type = $type ?? 'text';
    $value = $value ?? '';
    $required = $required ?? false;
    $options = $options ?? [];
    $selected = $selected ?? '';
    $help = $help ?? null;
    $errors = session('errors');
    $error = $errors ? $errors->first($name) : null;
    $displayValue = old($name, $value);
    $inputId = $name;
    $helpId = $help ? "{$name}-help" : null;
    $errorId = $error ? "{$name}-error" : null;
@endphp

<div class="mb-3">
    <label for="{{ $inputId }}" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger" aria-hidden="true">*</span>
        @endif
    </label>
    
    @if($type === 'select')
        <select 
            name="{{ $name }}" 
            id="{{ $inputId }}" 
            class="form-select @if($error) is-invalid @endif"
            @if($required) required aria-required="true" @endif
            @if($help) aria-describedby="{{ $helpId }}" @endif
            @if($error) aria-invalid="true" aria-describedby="{{ $errorId }}@if($help) {{ $helpId }}@endif" @endif
        >
            <option value="">Seleccionar...</option>
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" {{ ($selected == $optionValue) ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea 
            name="{{ $name }}" 
            id="{{ $inputId }}" 
            class="form-control @if($error) is-invalid @endif"
            @if($required) required aria-required="true" @endif
            @if($help) aria-describedby="{{ $helpId }}" @endif
            @if($error) aria-invalid="true" aria-describedby="{{ $errorId }}@if($help) {{ $helpId }}@endif" @endif
        >{{ $displayValue }}</textarea>
    @elseif($type === 'checkbox')
        <div class="form-check">
            <input 
                type="checkbox" 
                name="{{ $name }}" 
                id="{{ $inputId }}" 
                value="1"
                @if($displayValue) checked @endif
                class="form-check-input @if($error) is-invalid @endif"
                @if($required) required aria-required="true" @endif
                @if($help) aria-describedby="{{ $helpId }}" @endif
                @if($error) aria-invalid="true" aria-describedby="{{ $errorId }}@if($help) {{ $helpId }}@endif" @endif
            >
            <label class="form-check-label" for="{{ $inputId }}">{{ $label }}</label>
        </div>
    @else
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $inputId }}" 
            value="{{ $displayValue }}"
            class="form-control @if($error) is-invalid @endif"
            @if($required) required aria-required="true" @endif
            @if($help) aria-describedby="{{ $helpId }}" @endif
            @if($error) aria-invalid="true" aria-describedby="{{ $errorId }}@if($help) {{ $helpId }}@endif" @endif
        >
    @endif
    
    @if($help)
        <div class="form-text" id="{{ $helpId }}">{{ $help }}</div>
    @endif
    
    @if($error)
        <div class="invalid-feedback" id="{{ $errorId }}" role="alert">
            {{ $error }}
        </div>
    @endif
</div>