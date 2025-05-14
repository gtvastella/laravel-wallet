<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse &&
            !$this->hasStandardStructure($response)) {
            $data = $response->getData(true);
            $status = $response->getStatusCode();

            $standardResponse = [
                'success' => $status < 400,
                'message' => $this->getDefaultMessage($status),
                'data' => is_array($data) ? $data : ['result' => $data],
            ];

            $response->setData($standardResponse);
        }

        return $response;
    }

    private function hasStandardStructure(JsonResponse $response): bool
    {
        $data = $response->getData(true);

        return is_array($data) &&
               isset($data['success']) &&
               isset($data['message']);
    }

    private function getDefaultMessage(int $status): string
    {
        $messages = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            422 => 'Validation failed',
            500 => 'Server error',
        ];

        return $messages[$status] ?? 'Unknown status';
    }
}
