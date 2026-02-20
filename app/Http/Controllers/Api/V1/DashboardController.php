<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
class DashboardController extends BaseApiController
{
    #[OA\Get(
        path: "/dashboard",
        tags: ["Dashboard"],
        summary: "Get Dashboard Data",
        description: "Get authenticated user details and dashboard information",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "user",
                                    type: "object",
                                    description: "Authenticated user details"
                                ),
                                new OA\Property(
                                    property: "dashboard",
                                    type: "object",
                                    description: "Dashboard information",
                                    properties: [
                                        new OA\Property(
                                            property: "stats",
                                            type: "object",
                                            description: "Dashboard statistics",
                                            properties: [
                                                new OA\Property(
                                                    property: "total_users",
                                                    type: "integer",
                                                    description: "Total number of users"
                                                ),
                                                new OA\Property(
                                                    property: "active_users",
                                                    type: "integer",
                                                    description: "Number of active users"
                                                ),
                                                new OA\Property(
                                                    property: "new_signups",
                                                    type: "integer",
                                                    description: "Number of new signups"
                                                )
                                            ]
                                        ),
                                        new OA\Property(
                                            property: "recent_activity",
                                            type: "array",
                                            description: "List of recent activities",
                                            items: new OA\Items(
                                                type: "object",
                                                properties: [
                                                    new OA\Property(
                                                        property: "type",
                                                        type: "string",
                                                        description: "Type of activity"
                                                    ),
                                                    new OA\Property(
                                                        property: "timestamp",
                                                        type: "string",
                                                        format: "date-time",
                                                        description: "Timestamp of the activity"
                                                    )
                                                ]
                                            )
                                        )
                                    ]
                                )                            ]
                        )                    ]
                )            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            )
        ]   
    )]
    public function __invoke(Request $request)
    {
        // Return the dashboard data
        return $this->success([
            'data' => [
                'user' => $request->user(),
                'dashboard' => [
                    'stats' => [
                        'total_users' => 1500,
                        'active_users' => 1200,
                        'new_signups' => 50
                    ],
                    'recent_activity' => [
                        ['type' => 'login', 'timestamp' => now()->subMinutes(5)],
                        ['type' => 'update_profile', 'timestamp' => now()->subMinutes(10)],
                        ['type' => 'logout', 'timestamp' => now()->subMinutes(15)]
                    ]
                ]
            ]

        ], 'Dashboard data retrieved successfully');
    }
}
