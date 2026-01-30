<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cohost\StoreCohostRequest;
use App\Http\Requests\Cohost\UpdateCohostRequest;
use App\Models\Cohost;
use App\Services\CohostService;
use Illuminate\Http\Request;

class CohostController extends Controller
{
    public function index(Request $request, CohostService $cohostService)
    {
        $cohosts = $cohostService->listForHost($request->user());

        return response()->json($cohosts);
    }

    public function store(StoreCohostRequest $request, CohostService $cohostService)
    {
        $cohost = $cohostService->create($request->user(), $request->validated());

        return response()->json($cohost, 201);
    }

    public function update(
        UpdateCohostRequest $request,
        Cohost $cohost,
        CohostService $cohostService
    ) {
        $updated = $cohostService->update($request->user(), $cohost, $request->validated());

        return response()->json($updated);
    }

    public function destroy(Request $request, Cohost $cohost, CohostService $cohostService)
    {
        $cohostService->delete($request->user(), $cohost);

        return response()->json([
            'message' => 'Co-hôte supprimé.',
        ]);
    }
}
