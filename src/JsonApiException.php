<?php

namespace OpenSoutheners\LaravelApiable;

use Exception;

class JsonApiException extends Exception
{
    protected array $errors = [];

    /**
     * Add error to the stack of errors.
     */
    public function addError(
        string $title,
        string|null $detail = null,
        string|null $source = null,
        int|null $status = 500,
        int|string|null $code = null,
        array $trace = []
    ): void {
        $error = [];
        $error['title'] = $title;

        if ($detail) {
            $error['detail'] = $detail;
        }

        if ($source) {
            $error['source'] = ['pointer' => $source];
        }

        if ($status) {
            $error['status'] = (string) $status;
        }

        if ($code) {
            $error['code'] = (string) $code;
        }
        
        if (! empty($trace)) {
            $error['trace'] = $trace;
        }

        $this->errors[] = $error;
    }

    /**
     * Get errors array.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get exception errors to array.
     */
    public function toArray(): array
    {
        return [
            'errors' => $this->errors,
        ];
    }

    /**
     * Gets the exception as string.
     */
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }
}
