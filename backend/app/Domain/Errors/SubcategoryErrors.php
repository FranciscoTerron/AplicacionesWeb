<?php

namespace App\Domain\Errors;

class SubcategoryNotFoundError extends DomainError
{
    public function __construct(string $id)
    {
        parent::__construct("Subcategoría no encontrada: {$id}");
    }

    public function getErrorCode(): string
    {
        return 'SUBCATEGORY_NOT_FOUND';
    }

    public function getUserMessage(): string
    {
        return 'La subcategoría no fue encontrada.';
    }
}

class SubcategoryAlreadyExistsError extends DomainError
{
    public function __construct(string $name)
    {
        parent::__construct("La subcategoría '{$name}' ya existe");
    }

    public function getErrorCode(): string
    {
        return 'SUBCATEGORY_ALREADY_EXISTS';
    }

    public function getUserMessage(): string
    {
        return 'Ya existe una subcategoría con ese nombre.';
    }
}

class SubcategoryBelongsToAnotherCategoryError extends DomainError
{
    public function __construct()
    {
        parent::__construct('La subcategoría pertenece a otra categoría');
    }

    public function getErrorCode(): string
    {
        return 'SUBCATEGORY_WRONG_CATEGORY';
    }

    public function getUserMessage(): string
    {
        return 'La subcategoría pertenece a una categoría diferente.';
    }
}
