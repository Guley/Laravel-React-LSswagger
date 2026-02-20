<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: " Sample API Documentation V1",
    description: " Complete API documentation for Sample application with versioning support",
    contact: new OA\Contact(
        email: "contact@example.com"
    ),
    license: new OA\License(
        name: "Apache 2.0",
        url: "https://www.apache.org/licenses/LICENSE-2.0.html"
    )
)]
abstract class Controller
{
    //
}
