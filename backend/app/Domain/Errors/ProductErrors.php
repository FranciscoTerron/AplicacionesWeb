<?php

namespace App\Domain\Errors;

class ProductNotFoundError extends DomainError
{
    public function __construct(string $id)
    {
        parent::__construct("Producto no encontrado: {$id}");
    }

    public function getErrorCode(): string
    {
        return 'PRODUCT_NOT_FOUND';
    }

    public function getUserMessage(): string
    {
        return 'El producto no fue encontrado.';
    }
}

class ProductAlreadyExistsError extends DomainError
{
    public function __construct(string $name)
    {
        parent::__construct("El producto '{$name}' ya existe");
    }

    public function getErrorCode(): string
    {
        return 'PRODUCT_ALREADY_EXISTS';
    }

    public function getUserMessage(): string
    {
        return 'Ya existe un producto con ese nombre.';
    }
}

class ProductDeleteError extends DomainError
{
    public function __construct(string $reason)
    {
        parent::__construct("No se puede eliminar el producto: {$reason}");
    }

    public function getErrorCode(): string
    {
        return 'PRODUCT_DELETE_ERROR';
    }

    public function getUserMessage(): string
    {
        return 'No se puede eliminar el producto porque tiene pedidos asociados.';
    }
}
