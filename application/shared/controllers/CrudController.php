<?php

declare(strict_types=1);

namespace application\shared\controllers;

use orange\framework\controllers\BaseController;

/**
 * this is a user controller that others can extend it is not nessesary but it's nice to put commonly used code here
 */
abstract class CrudController extends BaseController
{
    // method to responds code
    protected array $restSuccessMap = [
        'getAll' => 200,
        'getById' => 200,
        'create' => 201,
        'update' => 202,
        'delete' => 202,
    ];

    // method to json response key
    protected array $restGetMap = [
        'getById' => 'record',
        'getAll' => 'records',
    ];

    protected function preformCRUD(string $model, string $method, array $args = [], ?string $jsonKey = null, ?int $success = -1): string
    {
        $this->data['json'] = [];
        $this->data['contentType'] = 'json';

        // throws an exception which is caught by
        // exceptionHandler(Throwable $exception): void
        // which sends output based on the exception thrown
        $results = $this->$model->$method(...$args);

        // if you get here it is a success
        $this->data['statusCode'] = ($success != -1) ? $success : $this->restSuccessMap[$method];

        // if our CRUD call returns json
        // what key should be used?
        if (isset($this->restGetMap[$method]) || $jsonKey !== null) {
            $this->data['json'][$jsonKey ?? $this->restGetMap[$method]] = $results;
        }

        // send the json response
        return $this->restResponse();
    }

    /**
     * send rest response
     * set in data object:
     *   statiusCode
     *   contentType
     *   json
     */
    public function restResponse(?int $statusCode = null, ?string $contentType = null, ?string $write = null): string
    {
        $statusCode = $statusCode ?? $this->data['statusCode'];
        $contentType = $contentType ?? $this->data['contentType'];
        $write = isset($this->data['json']) ? json_encode($this->data['json']) : $write;

        // use data statusCode, contentType & json to generate the response
        $this->output->responseCode($statusCode)->contentType($contentType)->write($write);

        return '';
    }
}
