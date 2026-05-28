@php
    $hasFilters = !empty($search) || !empty($statusFilter) || !empty($categoryFilter) || 
                  !empty($subcategoryFilter) || !empty($typeFilter) || !empty($roleFilter) ||
                  !empty($paymentFilter);
    $clearUrl = route($routeName) . '?page=1';
@endphp

@if($hasFilters)
    <a href="{{ $clearUrl }}" class="btn btn-sm btn-outline-secondary w-100 mt-1">Limpiar</a>
@endif