<?php

namespace App\Domain\Errors;

class ClientNotFoundError extends DomainError
{
    public function __construct(string $id)
    {
        parent::__construct("Cliente no encontrado: {$id}");
    }

    public function getErrorCode(): string
    {
        return 'CLIENT_NOT_FOUND';
    }

    public function getUserMessage(): string
    {
        return 'El cliente no fue encontrado.';
    }
}

class ClientAlreadyExistsError extends DomainError
{
    public function __construct(string $email)
    {
        parent::__construct("El cliente con email '{$email}' ya existe");
    }

    public function getErrorCode(): string
    {
        return 'CLIENT_ALREADY_EXISTS';
    }

    public function getUserMessage(): string
    {
        return 'Ya existe un cliente con ese correo electrónico.';
    }
}

class ClientDeleteError extends DomainError
{
    public function __construct(string $reason)
    {
        parent::__construct("No se puede eliminar el cliente: {$reason}");
    }

    public function getErrorCode(): string
    {
        return 'CLIENT_DELETE_ERROR';
    }

    public function getUserMessage(): string
    {
        return 'No se puede eliminar el cliente porque tiene pedidos asociados.';
    }
}
