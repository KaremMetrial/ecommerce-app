<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        // Add exceptions that shouldn't be reported
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        // Add inputs that shouldn't be flashed
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            return $this->renderValidationException($exception);
        }

        if ($exception instanceof AuthenticationException) {
            return $this->renderAuthenticationException($exception);
        }

        if ($exception instanceof AuthorizationException) {
            return $this->renderAuthorizationException($exception);
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->renderModelNotFoundException($exception);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->renderNotFoundHttpException($exception);
        }

        if ($exception instanceof AccessDeniedHttpException) {
            return $this->renderAccessDeniedHttpException($exception);
        }

        if ($exception instanceof UnprocessableEntityHttpException) {
            return $this->renderUnprocessableEntityHttpException($exception);
        }

        return $this->renderGenericException($exception);
    }

    /**
     * Render validation exception
     */
    protected function renderValidationException(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __('validation.failed'),
            'errors' => $exception->errors(),
            'code' => 422,
        ], 422);
    }

    /**
     * Render authentication exception
     */
    protected function renderAuthenticationException(AuthenticationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __('auth.failed'),
            'code' => 401,
        ], 401);
    }

    /**
     * Render authorization exception
     */
    protected function renderAuthorizationException(AuthorizationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __('auth.forbidden'),
            'code' => 403,
        ], 403);
    }

    /**
     * Render model not found exception
     */
    protected function renderModelNotFoundException(ModelNotFoundException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __('validation.ecommerce.product_not_found'),
            'code' => 404,
        ], 404);
    }

    /**
     * Render not found HTTP exception
     */
    protected function renderNotFoundHttpException(NotFoundHttpException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __('validation.ecommerce.page_not_found'),
            'code' => 404,
        ], 404);
    }

    /**
     * Render access denied HTTP exception
     */
    protected function renderAccessDeniedHttpException(AccessDeniedHttpException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __('validation.ecommerce.access_denied'),
            'code' => 403,
        ], 403);
    }

    /**
     * Render unprocessable entity HTTP exception
     */
    protected function renderUnprocessableEntityHttpException(UnprocessableEntityHttpException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage() ?: __('validation.ecommerce.invalid_request'),
            'code' => 422,
        ], 422);
    }

    /**
     * Render generic exception
     */
    protected function renderGenericException(Throwable $exception): JsonResponse
    {
        // Don't expose sensitive information in production
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => __('validation.ecommerce.server_error'),
                'code' => 500,
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'code' => 500,
            'exception' => class_basename($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => collect($exception->getTrace())->map(function ($trace) {
                return [
                    'file' => $trace['file'] ?? null,
                    'line' => $trace['line'] ?? null,
                    'function' => $trace['function'] ?? null,
                    'class' => $trace['class'] ?? null,
                    'type' => $trace['type'] ?? null,
                ];
            })->toArray(),
        ], 500);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        $this->renderable(function (Throwable $e, Request $request) {
            return $this->shouldReturnJson($request)
                ? $this->renderJsonException($e)
                : $this->renderWebException($e);
        });
    }

    /**
     * Determine if the exception should return JSON
     */
    protected function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson() ||
               $request->isJson() ||
               $request->is('api/*') ||
               $request->wantsJson();
    }

    /**
     * Render JSON exception
     */
    protected function renderJsonException(Throwable $exception): JsonResponse
    {
        return $this->render($request, $exception);
    }

    /**
     * Render web exception
     */
    protected function renderWebException(Throwable $exception): \Illuminate\Http\Response
    {
        return parent::render($request, $exception);
    }
}
