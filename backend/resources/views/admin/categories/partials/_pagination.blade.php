@if(isset($hasMore) && ($hasMore || ($page ?? 1) > 1))
<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        @if(($page ?? 1) > 1)
            <a href="{{ route('admin.categories.index', array_filter([
                'page' => ($page ?? 1) - 1,
                'after' => request('after_prev'),
                'search' => $search ?? null,
                'status' => $statusFilter ?? null,
            ])) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-chevron-left"></i> Anterior
            </a>
        @endif
    </div>
    <div><span class="text-muted">Página {{ $page ?? 1 }}</span></div>
    <div>
        @if($hasMore ?? false)
            <a href="{{ route('admin.categories.index', array_filter([
                'page' => ($page ?? 1) + 1,
                'after' => $lastDocumentId ?? null,
                'search' => $search ?? null,
                'status' => $statusFilter ?? null,
            ])) }}" class="btn btn-outline-primary btn-sm">
                Siguiente <i class="bi bi-chevron-right"></i>
            </a>
        @endif
    </div>
</div>
@endif
