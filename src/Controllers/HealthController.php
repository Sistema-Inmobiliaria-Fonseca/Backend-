<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use Throwable;

final class HealthController extends Controller
{
    public function __construct(
        private readonly Database $db,
        private readonly Config $config
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->respond([
            'name' => $this->config->get('app.name'),
            'version' => $this->config->get('app.version'),
            'env' => $this->config->get('app.env'),
            'docs' => 'Endpoints disponibles en /api/health',
            'endpoints' => [
                'GET /api' => 'Información del servicio.',
                'GET /api/health' => 'Estado de la API.',
                'GET /api/health/database' => 'Estado de la conexión a MySQL.',
            ],
        ]);
    }

    public function health(Request $request): Response
    {
        return $this->respond([
            'status' => 'ok',
            'app' => [
                'name' => $this->config->get('app.name'),
                'version' => $this->config->get('app.version'),
                'env' => $this->config->get('app.env'),
                'debug' => (bool) $this->config->get('app.debug'),
                'php' => PHP_VERSION,
                'timezone' => date_default_timezone_get(),
                'time' => date(DATE_ATOM),
            ],
        ]);
    }

    public function database(Request $request): Response
    {
        $start = microtime(true);

        try {
            $serverVersion = $this->db->serverVersion();
            $databaseName = $this->db->databaseName();
        } catch (Throwable $exception) {
            return Response::json([
                'success' => false,
                'error' => [
                    'code' => 503,
                    'message' => 'No se pudo conectar a la base de datos.',
                    'details' => (bool) $this->config->get('app.debug')
                        ? [$exception::class . ': ' . $exception->getMessage()]
                        : [],
                ],
            ], 503);
        }

        return $this->respond([
            'status' => 'ok',
            'connection' => [
                'driver' => 'mysql',
                'host' => $this->db->host(),
                'port' => $this->db->port(),
                'database' => $databaseName !== '' ? $databaseName : $this->db->name(),
                'charset' => $this->db->charset(),
                'server_version' => $serverVersion,
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ],
        ]);
    }
}
