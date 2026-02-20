<?php

namespace App\OpenApi\v1;
use OpenApi\Attributes as OA;
#[OA\SecurityScheme(
    securityScheme
    : "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter JWT Bearer token in format: Bearer {token}"     
)]
#[OA\SecurityScheme(
    securityScheme: "apiKey",
    type: "apiKey",
    in: "header",
    name: "X-API-Key",
    description: "API Key for authentication"
)]
#[OA\SecurityScheme(
    securityScheme: "oauth2",
    type: "oauth2",
    description: "OAuth2 authentication",
    flows: new OA\Flows(
        authorizationCode: new OA\Flow(
            authorizationUrl: "https://sample.com/oauth/authorize",     
            tokenUrl: "https://sample.com/oauth/token",
            scopes: [
                "read" => "Read access",
                "write" => "Write access",
                "admin" => "Administrative access"
            ]
        )
    )
)]
class SecuritySchemes
{
    // Security schemes definitions
}
