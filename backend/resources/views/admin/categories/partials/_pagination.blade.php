<!-- Pagination -->
@if($hasMore ?? false)
<div class="d-flex justify-content-end mt-3">
    <a href="{{ route('admin.categories.index', array_filter(['after' => $lastDocumentId, 'search' => $search, 'status' => $statusFilter])) }}"
       class="btn btn-outline-primary btn-sm">
        Siguiente <i class="bi bi-chevron-right"></i>
    </a>
</div>
@endif