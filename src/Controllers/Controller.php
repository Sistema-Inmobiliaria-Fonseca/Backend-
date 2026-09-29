<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

abstract class Controller
{
    protected function respond(mixed $data = null, int $status = 200, array $headers = []): Response
    {
        return Response::json(['success' => true, 'data' => $data], $status, $headers);
    }

    protected function created(mixed $data = null, array $headers = []): Response
    {
        return $this->respond($data, 201, $headers);
    }

    protected function noContent(): Response
    {
        return Response::noContent();
    }
}
