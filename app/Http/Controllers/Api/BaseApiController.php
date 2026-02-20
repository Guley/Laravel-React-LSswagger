<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;
#[OA\Tag(
    name: "Authentication",
    description: "Authentication related endpoints"
)]
#[OA\Tag(
    name: "Reset Password",
    description: "Endpoints for password reset functionality"
)]
class BaseApiController extends Controller
{
    use ApiResponse;

    protected $version;

    public function __construct()
    {
        $this->version = request()->route()->getPrefix();
    }
    
}
