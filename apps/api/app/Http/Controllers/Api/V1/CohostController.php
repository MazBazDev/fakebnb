<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cohost\StoreCohostRequest;
use App\Http\Requests\Cohost\UpdateCohostRequest;
use App\Models\Cohost;
use App\Services\CohostService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Cohosts', 'Délégation de co-hôtes')]
class CohostController extends Controller
{
    /**
     * Liste des co-hôtes.
     */
    public function index(Request $request, CohostService $cohostService)
    {
        $cohosts = $cohostService->listForHost(
            $request->user(),
            $request->integer('listing_id')
        );

        return response()->json($cohosts);
    }

    /**
     * Ajouter un co-hôte.
     */
    public function store(StoreCohostRequest $request, CohostService $cohostService)
    {
        $cohost = $cohostService->create($request->user(), $request->validated());

        return response()->json($cohost, 201);
    }

    /**
     * Modifier les permissions d'un co-hôte.
     */
    public function update(
        UpdateCohostRequest $request,
        Cohost $cohost,
        CohostService $cohostService
    ) {
        $updated = $cohostService->update($request->user(), $cohost, $request->validated());

        return response()->json($updated);
    }

    /**
     * Supprimer un co-hôte.
     */
    public function destroy(Request $request, Cohost $cohost, CohostService $cohostService)
    {
        $cohostService->delete($request->user(), $cohost);

        return response()->json([
            'message' => 'Co-hôte supprimé.',
        ]);
    }
}
