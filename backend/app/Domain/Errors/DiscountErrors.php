<?php

namespace App\Domain\Errors;

class DiscountNotFoundError extends DomainError
{
    public function __construct(string $id)
    {
        parent::__construct("Descuento no encontrado: {$id}");
    }

    public function getErrorCode(): string
    {
        return 'DISCOUNT_NOT_FOUND';
    }

    public function getUserMessage(): string
    {
        return 'El descuento no fue encontrado.';
    }
}

class DiscountAlreadyExistsError extends DomainError
{
    public function __construct(string $name)
    {
        parent::__construct("El descuento '{$name}' ya existe");
    }

    public function getErrorCode(): string
    {
        return 'DISCOUNT_ALREADY_EXISTS';
    }

    public function getUserMessage(): string
    {
        return 'Ya existe un descuento con ese código.';
    }
}

class DiscountExpiredError extends DomainError
{
    public function __construct(string $name)
    {
        parent::__construct("El descuento '{$name}' ha vencido");
    }

    public function getErrorCode(): string
    {
        return 'DISCOUNT_EXPIRED';
    }

    public function getUserMessage(): string
    {
        return 'El descuento ha vencido.';
    }
}
