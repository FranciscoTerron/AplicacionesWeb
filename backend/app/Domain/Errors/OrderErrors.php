<?php

namespace App\Domain\Errors;

class OrderNotFoundError extends DomainError
{
    public function __construct(string $id)
    {
        parent::__construct("Pedido no encontrado: {$id}");
    }

    public function getErrorCode(): string
    {
        return 'ORDER_NOT_FOUND';
    }

    public function getUserMessage(): string
    {
        return 'El pedido no fue encontrado.';
    }
}

class OrderAlreadyExistsError extends DomainError
{
    public function __construct(string $orderNumber)
    {
        parent::__construct("El pedido '{$orderNumber}' ya existe");
    }

    public function getErrorCode(): string
    {
        return 'ORDER_ALREADY_EXISTS';
    }

    public function getUserMessage(): string
    {
        return 'Ya existe un pedido con ese número.';
    }
}

class OrderCannotCancelError extends DomainError
{
    public function __construct(string $status)
    {
        parent::__construct("No se puede cancelar el pedido en estado: {$status}");
    }

    public function getErrorCode(): string
    {
        return 'ORDER_CANNOT_CANCEL';
    }

    public function getUserMessage(): string
    {
        return 'No se puede cancelar el pedido en su estado actual.';
    }
}

class OrderAlreadyPaidError extends DomainError
{
    public function __construct()
    {
        parent::__construct('El pedido ya está pagado');
    }

    public function getErrorCode(): string
    {
        return 'ORDER_ALREADY_PAID';
    }

    public function getUserMessage(): string
    {
        return 'El pedido ya ha sido pagado.';
    }
}
