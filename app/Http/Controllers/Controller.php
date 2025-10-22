<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "News Aggregator API",
    description: "API documentation for the News Aggregator application"
)]
#[OA\Server(
    url: "http://localhost:8080/api/v1",
    description: "Local development server"
)]
abstract class Controller
{
    //
}
