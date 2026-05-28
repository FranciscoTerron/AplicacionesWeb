@php
    $currentSort = $sort ?? 'name';
    $currentOrder = $order ?? 'asc';
    $newOrder = ($currentSort === $field && $currentOrder === 'asc') ? 'desc' : 'asc';
    $icon = $currentSort === $field 
        ? ($currentOrder === 'asc' ? ' ▲' : ' ▼') 
        : '';
    
    $url = request()->fullUrlWithQuery([
        'sort' => $field,
        'order' => $newOrder,
        'page' => 1
    ]);
@endphp
<a href="{{ $url }}" class="text-decoration-none">{{ $label }}{!! $icon !!}</a>