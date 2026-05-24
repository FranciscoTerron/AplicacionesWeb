@extends('layouts.admin')

@section('title', 'Importar ' . $entityLabel . ' - MA Piscinas')
@section('page-title', 'Importar ' . $entityLabel)
@section('page-subtitle', 'Carga masiva de datos desde archivo CSV')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Subir archivo CSV</h5>
                
                @if(session('importResults'))
                    @php $results = session('importResults'); @endphp
                    <div class="alert alert-info">
                        <h6>Resultado de la importación:</h6>
                        <ul class="mb-0">
                            <li>Total procesadas: {{ $results['total'] }}</li>
                            <li class="text-success">Exitosas: {{ $results['success'] }}</li>
                            <li class="text-danger">Fallidas: {{ $results['failed'] }}</li>
                        </ul>
                        @if(! empty($results['errors']))
                            <hr>
                            <h6>Errores:</h6>
                            <ul class="mb-0" style="max-height: 200px; overflow-y: auto;">
                                @foreach($results['errors'] as $error)
                                    <li class="text-danger">{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <form action="{{ route('admin.import.store', $entity) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Archivo CSV</label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">Tamaño máximo: 2MB. Formato: CSV con encabezados.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Importar
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Formato esperado</h5>
                <p class="card-text">El archivo CSV debe tener encabezados en la primera fila.</p>
                
                @if($entity === 'categories')
                    <h6>Categorías:</h6>
                    <pre class="bg-light p-2 small">name,description,active,order<br>Piscinas,"Cat de piscinas",1,1</pre>
                @elseif($entity === 'subcategories')
                    <h6>Subcategorías:</h6>
                    <pre class="bg-light p-2 small">name,description,category_id,active,order<br>Piscinas Urbanas,,piscinas,1,1</pre>
                @elseif($entity === 'products')
                    <h6>Productos:</h6>
                    <pre class="bg-light p-2 small">name,description,sku,price,stock,active,category_id,subcategory_id<br>Piscina 8x4,"Desc",PSC-001,25000,5,1,piscinas,psc-urbana</pre>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection