<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Exceptions\HttpException;
use App\Core\Exceptions\MethodNotAllowedHttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Throwable;

final class ErrorHandlerMiddleware implements Middleware
{
    public function __construct(
        private readonly bool $debug = false,
        private readonly ?string $logFile = null
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (MethodNotAllowedHttpException $exception) {
            return $this->render(
                405,
                $exception->getMessage(),
                $exception->getAllowedMethods() !== []
                    ? ['Allow' => implode(', ', $exception->getAllowedMethods())]
                    : []
            );
        } catch (HttpException $exception) {
            return $this->render($exception->getStatusCode(), $exception->getMessage(), [], $exception->getDetails());
        } catch (Throwable $exception) {
            $this->log($exception);

            return $this->render(
                500,
                $this->debug ? $exception->getMessage() : 'Error interno del servidor.',
                [],
                $this->debug
                    ? [
                        'exception' => $exception::class,
                        'file' => $exception->getFile() . ':' . $exception->getLine(),
                        'trace' => array_slice(explode("\n", $exception->getTraceAsString()), 0, 15),
                    ]
                    : []
            );
        }
    }

    private function render(int $status, string $message, array $headers = [], array $details = []): Response
    {
        $error = [
            'code' => $status,
            'message' => $message,
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        return Response::json(['success' => false, 'error' => $error], $status, $headers);
    }

    private function log(Throwable $exception): void
    {
        if ($this->logFile === null) {
            return;
        }

        $directory = dirname($this->logFile);

        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $entry = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        @file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
