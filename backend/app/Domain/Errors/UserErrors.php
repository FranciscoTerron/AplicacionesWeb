<?php

namespace App\Domain\Errors;

class UserNotFoundError extends DomainError
{
    public function __construct(string $identifier)
    {
        parent::__construct("Usuario no encontrado: {$identifier}");
    }

    public function getErrorCode(): string
    {
        return 'USER_NOT_FOUND';
    }

    public function getUserMessage(): string
    {
        return 'El usuario no fue encontrado.';
    }
}

class UserAlreadyExistsError extends DomainError
{
    public function __construct(string $identifier)
    {
        parent::__construct("El usuario '{$identifier}' ya existe");
    }

    public function getErrorCode(): string
    {
        return 'USER_ALREADY_EXISTS';
    }

    public function getUserMessage(): string
    {
        return 'Ya existe un usuario con esos datos.';
    }
}

class InvalidUserDataError extends DomainError
{
    public function __construct(string $details = '')
    {
        $message = 'Datos de usuario inválidos';
        if ($details) {
            $message .= ": {$details}";
        }
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'INVALID_USER_DATA';
    }

    public function getUserMessage(): string
    {
        return 'Los datos proporcionados para el usuario son inválidos.';
    }
}

class UserCreationFailedError extends DomainError
{
    public function __construct(string $reason = '')
    {
        $message = 'Error al crear el usuario';
        if ($reason) {
            $message .= ": {$reason}";
        }
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'USER_CREATION_FAILED';
    }

    public function getUserMessage(): string
    {
        return 'No se pudo crear el usuario. Intente nuevamente.';
    }
}

class UserUpdateFailedError extends DomainError
{
    public function __construct(string $reason = '')
    {
        $message = 'Error al actualizar el usuario';
        if ($reason) {
            $message .= ": {$reason}";
        }
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'USER_UPDATE_FAILED';
    }

    public function getUserMessage(): string
    {
        return 'No se pudo actualizar el usuario. Intente nuevamente.';
    }
}

class UserDeletionFailedError extends DomainError
{
    public function __construct(string $reason = '')
    {
        $message = 'Error al eliminar el usuario';
        if ($reason) {
            $message .= ": {$reason}";
        }
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'USER_DELETION_FAILED';
    }

    public function getUserMessage(): string
    {
        return 'No se pudo eliminar el usuario.';
    }
}

class InsufficientPermissionsError extends DomainError
{
    public function __construct(string $action = '')
    {
        $message = 'Permisos insuficientes';
        if ($action) {
            $message .= " para {$action}";
        }
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'INSUFFICIENT_PERMISSIONS';
    }

    public function getUserMessage(): string
    {
        return 'No tiene permisos suficientes para realizar esta acción.';
    }
}
