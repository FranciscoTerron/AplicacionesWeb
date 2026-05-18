<?php

namespace App\Domain\Errors;

class CategoryNotFoundError extends DomainError
{
    public function __construct(string $id)
    {
        parent::__construct("Categoría no encontrada: {$id}");
    }

    public function getErrorCode(): string
    {
        return 'CATEGORY_NOT_FOUND';
    }

    public function getUserMessage(): string
    {
        return 'La categoría no fue encontrada.';
    }
}

class CategoryAlreadyExistsError extends DomainError
{
    public function __construct(string $name)
    {
        parent::__construct("La categoría '{$name}' ya existe");
    }

    public function getErrorCode(): string
    {
        return 'CATEGORY_ALREADY_EXISTS';
    }

    public function getUserMessage(): string
    {
        return 'Ya existe una categoría con ese nombre.';
    }
}

class CategoryDeleteError extends DomainError
{
    public function __construct(string $reason)
    {
        parent::__construct("No se puede eliminar la categoría: {$reason}");
    }

    public function getErrorCode(): string
    {
        return 'CATEGORY_DELETE_ERROR';
    }

    public function getUserMessage(): string
    {
        return 'No se puede eliminar la categoría porque tiene registros asociados.';
    }
}
