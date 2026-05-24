@if(isset($hasMore) && ($hasMore || ($page ?? 1) > 1))
<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        @if(($page ?? 1) > 1)
            <a href="{{ route('admin.products.index', array_filter([
                'page' => ($page ?? 1) - 1,
                'after' => null,
                'search' => $search ?? null,
                'category' => $categoryFilter ?? null,
                'subcategory' => $subcategoryFilter ?? null,
                'status' => $statusFilter ?? null,
                'per_page' => $perPage ?? 10,
                'sort' => $sort ?? null,
                'order' => $order ?? null,
            ])) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-chevron-left"></i> Anterior
            </a>
        @endif
        @if($totalFiltered ?? null)
            @php
                $p = $page ?? 1;
                $pp = $perPage ?? 10;
                $tf = $totalFiltered ?? 0;
                $from = ($p - 1) * $pp + 1;
                $to = min($p * $pp, $tf);
            @endphp
            <span class="text-muted small">Mostrando {{ $from }}-{{ $to }} de {{ $tf }} resultados</span>
        @endif
    </div>
    <div class="d-flex align-items-center gap-2">
        <label class="text-muted small mb-0">Por página:</label>
        <select class="form-select form-select-sm per-page-select" style="width:auto" data-route="admin.products.index">
            @foreach([10, 25, 50, 100] as $opt)
                <option value="{{ $opt }}" @selected(($perPage ?? 10) == $opt)>{{ $opt }}</option>
            @endforeach
        </select>
    </div>
    <div>
        @if($hasMore ?? false)
            <a href="{{ route('admin.products.index', array_filter([
                'page' => ($page ?? 1) + 1,
                'after' => $lastDocumentId ?? null,
                'search' => $search ?? null,
                'category' => $categoryFilter ?? null,
                'subcategory' => $subcategoryFilter ?? null,
                'status' => $statusFilter ?? null,
                'per_page' => $perPage ?? 10,
                'sort' => $sort ?? null,
                'order' => $order ?? null,
            ])) }}" class="btn btn-outline-primary btn-sm">
                Siguiente <i class="bi bi-chevron-right"></i>
            </a>
        @endif
    </div>
</div>
@endif

