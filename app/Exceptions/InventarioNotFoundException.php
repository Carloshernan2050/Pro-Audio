<?php

namespace App\Exceptions;

use Exception;

/**
 * @codeCoverageIgnore
 */
class InventarioNotFoundException extends Exception
{
    public function __construct(string $message = 'El producto del inventario seleccionado no existe.')
    {
        parent::__construct($message);
    }
}

