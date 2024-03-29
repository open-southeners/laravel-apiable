<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler implements Responsable
{
    protected JsonApiException $jsonApiException;

    protected array $headers = [];

    /**
     * @param  \Throwable|\Illuminate\Validation\ValidationException  $exception
     */
    public function __construct(
        protected Throwable $exception,
        protected ?bool $withTrace = null
    ) {
        $this->jsonApiException = new JsonApiException();
    }

    /**
     * Check whether include error trace.
     */
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
            max(array_column($this->jsonApiException->getErrors(), 'status')),
            array_merge(
                $this->exception instanceof HttpExceptionInterface ? $this->exception->getHeaders() : [],
                $this->headers
            )
        );
    }

    /**
     * Add header to the resulting response.
     */
    public function withHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Handle any other type of exception.
     *
     * @param  \Illuminate\Http\Request  $request
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

        /**
         * When authentication exception need to return proper error code as Laravel framework
         * is completely inconsistent with its exceptions...
         */
        if ($this->exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
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
