<?php

namespace App\Http\Controllers\Traits;

use OpenApi\Attributes as OA;

trait OpenApiResponses 
{
    /**
     * Standard 400 Bad Request response
     */
    #[OA\Response(
        response: 'BadRequest',
        description: 'Bad Request',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Bad Request'),
                new OA\Property(property: 'errors', type: 'object')
            ]
        )
    )]
    public function badRequest() {}

    /**
     * Standard 404 Not Found response
     */
    #[OA\Response(
        response: 'NotFound',
        description: 'Resource not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Resource not found')
            ]
        )
    )]
    public function notFound() {}

    /**
     * Standard 422 Validation Error response
     */
    #[OA\Response(
        response: 'ValidationError',
        description: 'Validation Error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object')
            ]
        )
    )]
    public function validationError() {}

    /**
     * Standard 500 Server Error response
     */
    #[OA\Response(
        response: 'ServerError',
        description: 'Internal Server Error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Internal Server Error')
            ]
        )
    )]
    public function serverError() {}
}