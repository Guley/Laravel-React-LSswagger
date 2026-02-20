<?php
namespace App\OpenApi\v1;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Sample API Documentation V1",
    description: "Complete API documentation for Sample application with versioning support",
    termsOfService: "https://sample.com/cookie-policy/",
    contact: new OA\Contact(
        name: "Sample API Team",
        email: "info@sample.com",
        url: "https://sample.com"
    ),
    license: new OA\License(
        name: "MIT",
        url: "https://opensource.org/licenses/MIT"
    )
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "Development Server"
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_PRODUCTION_HOST,
    description: "Production Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter JWT Bearer token in format: Bearer {token}"
)]
#[OA\Tag(
    name: "Authentication V1",
    description: "Authentication endpoints for API V1"
)]
class OpenApiInfo
{
    // OpenAPI base configuration
}
