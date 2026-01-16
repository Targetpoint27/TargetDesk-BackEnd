<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;

class ApiController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/v1/health",
     *     tags={"System"},
     *     summary="Health check endpoint",
     *     description="Check if the API is running",
     *     @OA\Response(
     *         response=200,
     *         description="API is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API is running"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="version", type="string", example="1.0.0"),
     *                 @OA\Property(property="timestamp", type="string", example="2026-01-08T09:00:00Z")
     *             )
     *         )
     *     )
     * )
     */
    public function health(): JsonResponse
    {
        return $this->successResponse([
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ], 'API is running');
    }

    /**
     * @OA\Get(
     *     path="/v1/user",
     *     tags={"User"},
     *     summary="Get authenticated user profile",
     *     description="Retrieve the profile of the currently authenticated user",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function user(): JsonResponse
    {
        return $this->successResponse(auth()->user(), 'User profile retrieved');
    }
}