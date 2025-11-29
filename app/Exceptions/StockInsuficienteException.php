<?php

namespace App\Exceptions;

use Exception;

/**
 * @codeCoverageIgnore
 */
class StockInsuficienteException extends Exception
{
    public function __construct(string $nombreProducto, int $disponible, int $solicitado)
    {
        $message = "No hay suficiente stock disponible para '{$nombreProducto}'. Disponible: {$disponible}, Solicitado: {$solicitado}";
        parent::__construct($message);
    }
}

