<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Ringover\RingoverService;
use Illuminate\Http\Request;

use App\Http\Requests\Ringover\CallHistoryRequest;
use App\Http\Resources\Ringover\CallResource;

class RingoverCallController extends Controller
{
    protected $ringoverService;

    public function __construct(RingoverService $ringoverService)
    {
        $this->ringoverService = $ringoverService;
    }

    /**
     * @OA\Get(
     * path="/api/v1/ringover/calls",
     * summary="Get Ringover call history",
     * description="Fetches a list of international calls from the Ringover API and transforms them for the TargetDesk UI.",
     * tags={"Ringover"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="limit_count",
     * in="query",
     * description="Number of calls to return",
     * required=false,
     * @OA\Schema(type="integer", default=20)
     * ),
     * @OA\Parameter(
     * name="direction",
     * in="query",
     * description="Filter by direction (in/out)",
     * required=false,
     * @OA\Schema(type="string", enum={"in", "out"})
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CallResource")),
     * @OA\Property(property="meta", type="object")
     * )
     * ),
     * @OA\Response(response=401, description="Unauthenticated"),
     * @OA\Response(response=500, description="Ringover API connection error")
     * )
     */
    public function index(CallHistoryRequest $request)
    {
        $rawData = $this->ringoverService->getCallHistory($request->validated());

        if (!$rawData || !isset($rawData['call_list'])) {
            return response()->json(['message' => 'Error fetching history'], 500);
        }

        // Wrap the list in our clean Resource
        return CallResource::collection($rawData['call_list'])
        ->additional([
            'meta' => [
                'total_count' => $rawData['total_call_count'] ?? 0,
                'missed_count' => $rawData['total_missed_call_count'] ?? 0
            ]
        ]);
    }
}