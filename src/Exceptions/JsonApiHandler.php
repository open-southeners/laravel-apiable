<?php

namespace OpenSoutheners\LaravelApiable\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class JsonApiHandler
{
    /**
     * Create new class instance.
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Throwable $e, $request)
    {
        if ($request->wantsJsonApi()) {
            return $this->renderJsonApi($e, $request);
        }
    }

    /**
     * Render json response based on JSON:API standard for errors.
     *
     * @param  \Throwable  $e
     * @param  mixed  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function renderJsonApi(Throwable $e, $request)
    {
        $response = ['errors' => []];

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($e instanceof HttpExceptionInterface || method_exists($e, 'getStatusCode')) {
            /** @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e */
            $statusCode = $e->getStatusCode();
        }

        if ($e instanceof ValidationException) {
            /** @var \Illuminate\Validation\ValidationException $e */
            $statusCode = 422;

            foreach ($e->errors() as $errorSource => $errors) {
                foreach ($errors as $error) {
                    $response['errors'][] = [
                        'code' => $statusCode,
                        'title' => $error,
                        'source' => [
                            'pointer' => $errorSource,
                        ],
                    ];
                }
            }
        }

        if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $response['errors'][0]['code'] = $e->getCode() ?: $statusCode;
            $response['errors'][0]['title'] = $e->getMessage();
            $response['errors'][0]['trace'] = $e->getTrace();
        }

        return response()->json($response, $statusCode);
    }
}
