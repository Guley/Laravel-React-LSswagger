<?php

namespace App\OpenApi\v1;

use OpenApi\Attributes as OA;
#[OA\Tag(
    name: "Authentication",
    description: "Authentication endpoints for V1",
    externalDocs: new OA\ExternalDocumentation(
        description: "Authentication Guide",
        url: "https://docs.sample.com/authentication"
    )
)]
#[OA\Tag(
    name: "Users V1",
    description: "User management endpoints for V1"
)]
#[OA\Tag(
    name: "System",
    description: "System and health check endpoints"
)]
#[OA\Tag(
    name: "Admin",
    description: "Administrative endpoints"
)]
class Tags
{
    // API tags definitions
}
