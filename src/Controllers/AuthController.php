<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $service)
    {
    }

    public function login(Request $request): Response
    {
        return $this->respond($this->service->iniciarSesion($request->all()));
    }

    public function me(Request $request): Response
    {
        return $this->respond($request->user());
    }
}