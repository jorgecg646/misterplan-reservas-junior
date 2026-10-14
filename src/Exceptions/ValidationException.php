<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción personalizada para errores de validación.
 * Permite transportar la lista de errores por campo para devolverlos en la respuesta JSON.
 */
class ValidationException extends Exception
{
    private array $errores;

    public function __construct(array $errores, string $message = "Error de validación")
    {
        parent::__construct($message, 422);
        $this->errores = $errores;
    }

    public function getErrores(): array
    {
        return $this->errores;
    }
}
