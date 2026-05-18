<!-- Pagination -->
@if(isset($hasMore) && ($hasMore || ($page ?? 1) > 1))
<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        @if(($page ?? 1) > 1)
            <a href="{{ route('admin.discounts.index', array_merge(['page' => ($page ?? 1) - 1, 'after' => request('after_prev')], array_filter(['search' => $search ?? '', 'type' => $typeFilter ?? '', 'status' => $statusFilter ?? '']))) }}"
               class="btn btn-outline-primary btn-sm">
               <i class="bi bi-chevron-left"></i> Anterior
            </a>
        @endif
    </div>
    <div>
        <span class="text-muted">Página {{ $page ?? 1 }}</span>
    </div>
    <div>
        @if($hasMore ?? false)
            <a href="{{ route('admin.discounts.index', array_merge(['page' => ($page ?? 1) + 1, 'after' => $lastDocumentId ?? ''], array_filter(['search' => $search ?? '', 'type' => $typeFilter ?? '', 'status' => $statusFilter ?? '']))) }}"
               class="btn btn-outline-primary btn-sm">
               Siguiente <i class="bi bi-chevron-right"></i>
            </a>
        @endif
    </div>
</div>
@endif