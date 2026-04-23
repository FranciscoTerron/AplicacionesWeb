@extends('layouts.admin')

@section('title', 'Órdenes - MA Piscinas')
@section('page-title', 'Órdenes')

@section('content')
@component('admin.components.data-table', [
    'title' => 'Todas las Órdenes',
    'columns' => [
        ['key' => 'id', 'label' => 'ID'],
        ['key' => 'cliente', 'label' => 'Cliente'],
        ['key' => 'total', 'label' => 'Total'],
        ['key' => 'estado', 'label' => 'Estado'],
        ['key' => 'fecha', 'label' => 'Fecha']
    ],
    'data' => [
        ['id' => '#ORD-001', 'cliente' => 'Juan Pérez', 'total' => '$1,250', 'estado' => 'Completado', 'fecha' => '22/04/2026'],
        ['id' => '#ORD-002', 'cliente' => 'María González', 'total' => '$890', 'estado' => 'Procesando', 'fecha' => '22/04/2026'],
        ['id' => '#ORD-003', 'cliente' => 'Carlos López', 'total' => '$2,100', 'estado' => 'Pendiente', 'fecha' => '21/04/2026']
    ]
])
@endcomponent
@endsection