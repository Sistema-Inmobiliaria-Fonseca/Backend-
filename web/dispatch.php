<?php

declare(strict_types=1);

// Delegacion del front controller, que vive fuera del Document Root para que
// la carpeta web no contenga logica de la aplicacion. Ver bootstrap/front.php.
return require dirname(__DIR__) . '/bootstrap/front.php';