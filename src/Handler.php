<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler implements Responsable
{
    protected JsonApiException $jsonApiException;

    public function __construct(
        protected Throwable $exception,
        protected bool|null $withTrace = null
    ) {
        $this->jsonApiException = new JsonApiException();
    }

    protected function includesTrace(): bool
    {
        return (bool) ($this->withTrace ?? env('APP_DEBUG'));
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): JsonResponse
    {
        match (true) {
            $this->exception instanceof ValidationException => $this->handleValidation($request),
            default => $this->handleException($request),
        };

        return new JsonResponse(
            $this->jsonApiException->toArray(),
            max(array_column($this->jsonApiException->getErrors(), 'status'))
        );
    }

    /**
     * Handle any other type of exception.
     * 
     * @param \Illuminate\Http\Request $request
     */
    protected function handleException($request): void
    {
        $code = null;
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = $this->exception->getMessage();
        $trace = $this->exception->getTrace();

        if (
            $this->exception instanceof HttpExceptionInterface
                || method_exists($this->exception, 'getStatusCode')
        ) {
            $statusCode = $this->exception->getStatusCode();
        }

        if (! $this->includesTrace()) {
            $message = 'Internal server error.';
            $trace = [];
        }

        if ($this->exception instanceof QueryException && $this->includesTrace()) {
            $code = $this->exception->getCode();
        }

        $this->jsonApiException->addError(title: $message, status: $statusCode, code: $code, trace: $trace);
    }

    /**
     * Handle validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    protected function handleValidation($request): void
    {
        $status = $this->exception->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY;

        foreach ($this->exception->errors() as $errorSource => $errors) {
            foreach ($errors as $error) {
                $this->jsonApiException->addError(title: $error, source: $errorSource, status: $status);
            }
        }
    }
}
