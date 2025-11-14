<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ErrorController extends Controller
{
    /**
     * Handle 404 Not Found errors.
     */
    public function notFound(Request $request)
    {
        if ($request->expectsJson()) {
            return $this->notFoundResponse('The requested resource was not found');
        }

        return response()->view('errors.404', [], 404);
    }

    /**
     * Handle 403 Forbidden errors.
     */
    public function forbidden(Request $request)
    {
        if ($request->expectsJson()) {
            return $this->forbiddenResponse('You do not have permission to access this resource');
        }

        return response()->view('errors.403', [], 403);
    }

    /**
     * Handle 500 Internal Server Error.
     */
    public function internalServerError(Request $request)
    {
        if ($request->expectsJson()) {
            return $this->serverErrorResponse('An internal server error occurred');
        }

        return response()->view('errors.500', [], 500);
    }

    /**
     * Handle 422 Validation Error.
     */
    public function validationError(Request $request)
    {
        if ($request->expectsJson()) {
            return $this->validationErrorResponse([
                'message' => 'The given data was invalid.',
                'errors' => session('errors')?->getMessages() ?? []
            ]);
        }

        return response()->view('errors.422', [], 422);
    }

    /**
     * Handle 429 Too Many Requests.
     */
    public function tooManyRequests(Request $request)
    {
        if ($request->expectsJson()) {
            return $this->errorResponse('Too many requests. Please try again later.', null, 429);
        }

        return response()->view('errors.429', [], 429);
    }

    /**
     * Handle 503 Service Unavailable.
     */
    public function serviceUnavailable(Request $request)
    {
        if ($request->expectsJson()) {
            return $this->errorResponse('Service temporarily unavailable. Please try again later.', null, 503);
        }

        return response()->view('errors.503', [], 503);
    }

    /**
     * Handle generic error page.
     */
    public function generic(Request $request, $code = 500)
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        $message = $messages[$code] ?? 'Unknown Error';

        if ($request->expectsJson()) {
            return $this->errorResponse($message, null, $code);
        }

        return response()->view('errors.generic', [
            'code' => $code,
            'message' => $message
        ], $code);
    }
}
