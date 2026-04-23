@php
    $columns = isset($columns) ? $columns : [];
    $data = isset($data) ? $data : [];
    $title = isset($title) ? $title : null;
    $actions = isset($actions) ? $actions : null;
    $emptyMessage = empty($emptyMessage) ? 'No hay datos disponibles' : $emptyMessage;
    $pagination = isset($pagination) ? $pagination : null;
@endphp

<div class="data-table">
    @if(!empty($title))
        <div class="data-table__header">
            <h3 class="data-table__title">{{ $title }}</h3>
            @if(!empty($actions))
                <div class="data-table__actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="data-table__wrapper">
        <table class="data-table__table">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th class="data-table__th">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if(count($data) > 0)
                    @foreach($data as $row)
                        <tr class="data-table__tr">
                            @foreach($columns as $column)
                                <td class="data-table__td">
                                    @if(isset($column['render']))
                                        {{ call_user_func($column['render'], $row) }}
                                    @else
                                        {{ isset($row[$column['key']]) ? $row[$column['key']] : '' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ count($columns) }}" class="data-table__empty">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if(!empty($pagination))
        <div class="data-table__pagination">
            {{ $pagination }}
        </div>
    @endif
</div>

<style>
.data-table {
    background: var(--white);
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: hidden;
}

.data-table__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
}

.data-table__title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
}

.data-table__actions {
    display: flex;
    gap: 0.5rem;
}

.data-table__wrapper {
    overflow-x: auto;
}

.data-table__table {
    width: 100%;
    border-collapse: collapse;
}

.data-table__th {
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: var(--bg-light);
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}

.data-table__td {
    padding: 1rem;
    font-size: 0.9rem;
    color: var(--text);
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.data-table__tr:last-child .data-table__td {
    border-bottom: none;
}

.data-table__tr:hover {
    background: var(--bg-light);
}

.data-table__empty {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.data-table__pagination {
    display: flex;
    justify-content: center;
    padding: 1rem;
    border-top: 1px solid var(--border);
}
</style>