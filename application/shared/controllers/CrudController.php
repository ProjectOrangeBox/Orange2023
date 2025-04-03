<?php

declare(strict_types=1);

namespace application\shared\controllers;

use peels\validate\exceptions\ValidationFailed;
use orange\framework\controllers\BaseController;

/**
 * this is a user controller that others can extend it is not nessesary but it's nice to put commonly used code here
 */
abstract class CrudController extends BaseController
{
    // method to responds code
    protected array $restSuccessMap = [
        'getNew' => 200,
        'getAll' => 200,
        'getById' => 200,
        'create' => 201,
        'update' => 202,
        'delete' => 202,
    ];

    // method to json response key
    protected array $restGetMap = [];

    protected function preformCRUD(string $model, string $method, array $args = [], ?string $jsonKey = null, ?int $success = -1): string
    {
        $this->data['json'] = [];
        $this->data['contentType'] = 'json';

        // throws an exception on error(s)
        $results = $this->$model->$method(...$args);

        // if you get here it is a success
        $this->data['statusCode'] = ($success != -1) ? $success : $this->restSuccessMap[$method];

        // if our CRUD call returns json
        // what key should be used?
        if (isset($this->restGetMap[$method]) || $jsonKey !== null) {
            $this->data['json'][$jsonKey ?? $this->restGetMap[$method]] = $results;
        } else {
            $this->data['json'] = $results;
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
    protected function restResponse(?int $statusCode = null, ?string $contentType = null, ?string $write = null): string
    {
        $statusCode = $statusCode ?? $this->data['statusCode'];
        $contentType = $contentType ?? $this->data['contentType'];
        $write = isset($this->data['json']) ? json_encode($this->data['json']) : $write ?? '';

        // use data statusCode, contentType & json to generate the response
        $this->output->responseCode($statusCode)->contentType($contentType)->write($write);

        return '';
    }

    /**
     * convert this to the front end format
     * so it can be directly inserted into the model
     * 
     * @param ValidationFailed $vf 
     * @return string 
     */
    protected function rest406(ValidationFailed $vf): string
    {
        // set rv-class-is-invalid="validation.invalid.firstname"
        foreach ($vf->getKeys() as $key) {
            $this->data['json']['validation']['invalid'][$key] = true;
        }

        // array of errors <div rv-each-row="validation.array">
        $this->data['json']['validation']['array'] = $vf->getErrorsAsArray();
        // show dialog rv-theme-modal-show="validation.show"
        $this->data['json']['validation']['show'] = true;

        return $this->restResponse(406);
    }
}
