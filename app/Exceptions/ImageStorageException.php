<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepción lanzada cuando hay un error al guardar una imagen en el almacenamiento.
 *
 * @codeCoverageIgnore
 */
class ImageStorageException extends RuntimeException
{
    public function __construct(string $message = 'Error al guardar la imagen. Verifica los permisos del directorio.')
    {
        parent::__construct($message);
    }
}

