@extends('layouts.admin')

@section('title', 'Clientes - MA Piscinas')
@section('page-title', 'Clientes')

@section('content')
@component('admin.components.data-table', [
    'title' => 'Todos los Clientes',
    'columns' => [
        ['key' => 'id', 'label' => 'ID'],
        ['key' => 'nombre', 'label' => 'Nombre'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'telefono', 'label' => 'Teléfono'],
        ['key' => 'registrado', 'label' => 'Registrado']
    ],
    'data' => [
        ['id' => '#CLI-001', 'nombre' => 'Juan Pérez', 'email' => 'juan@email.com', 'telefono' => '123456789', 'registrado' => '15/03/2026'],
        ['id' => '#CLI-002', 'nombre' => 'María González', 'email' => 'maria@email.com', 'telefono' => '987654321', 'registrado' => '20/03/2026'],
        ['id' => '#CLI-003', 'nombre' => 'Carlos López', 'email' => 'carlos@email.com', 'telefono' => '456789123', 'registrado' => '05/04/2026']
    ]
])
@endcomponent
@endsection