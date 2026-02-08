<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\CallMotif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CallMotifController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/call-motifs",
     *     summary="Lister tous les motifs d'appel",
     *     description="Récupère la liste de tous les motifs avec structure hiérarchique",
     *     tags={"Admin - Call Motifs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tree",
     *         in="query",
     *         description="Retourner en structure arborescente (true/false)",
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Response(response=200, description="Liste des motifs récupérée")
     * )
     */
    public function index(Request $request)
    {
        try {
            $useTree = $request->query('tree', false);

            if ($useTree) {
                // Return hierarchical tree structure
                $motifs = CallMotif::with(['children.children', 'department'])
                                   ->rootOnly()
                                   ->ordered()
                                   ->get();
            } else {
                // Return flat list
                $motifs = CallMotif::with(['parent', 'department'])
                                   ->ordered()
                                   ->get();
            }

            Log::info('Liste des motifs consultée', [
                'consulted_by' => Auth::id(),
                'total' => $motifs->count(),
                'tree_mode' => $useTree,
            ]);

            return $this->successResponse(
                $motifs,
                'Liste des motifs récupérée',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des motifs', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération des motifs',
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/call-motifs",
     *     summary="Créer un motif d'appel",
     *     tags={"Admin - Call Motifs"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label", "category"},
     *             @OA\Property(property="label", type="string", example="Problème de facturation"),
     *             @OA\Property(property="category", type="string", enum={"info", "reclamation", "support", "commercial", "autre"}, example="reclamation"),
     *             @OA\Property(property="parent_id", type="integer", example=null),
     *             @OA\Property(property="department_id", type="integer", example=4),
     *             @OA\Property(property="sla_hours", type="integer", example=24),
     *             @OA\Property(property="suggested_script", type="string", example="Bonjour, concernant votre facture..."),
     *             @OA\Property(property="display_order", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Motif créé avec succès")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'label' => 'required|string|max:255',
                'category' => 'required|in:info,reclamation,support,commercial,autre',
                'parent_id' => 'nullable|exists:call_motifs,id',
                'department_id' => 'nullable|exists:departments,id',
                'sla_hours' => 'nullable|integer|min:1',
                'suggested_script' => 'nullable|string',
                'display_order' => 'nullable|integer|min:0',
            ], [
                'label.required' => 'Le libellé du motif est obligatoire',
                'category.required' => 'La catégorie est obligatoire',
                'category.in' => 'Catégorie invalide',
                'parent_id.exists' => 'Le motif parent sélectionné n\'existe pas',
                'department_id.exists' => 'Le département sélectionné n\'existe pas',
                'sla_hours.min' => 'Le SLA doit être d\'au moins 1 heure',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            $motif = CallMotif::create([
                'label' => $request->label,
                'category' => $request->category,
                'parent_id' => $request->parent_id,
                'department_id' => $request->department_id,
                'sla_hours' => $request->sla_hours,
                'suggested_script' => $request->suggested_script,
                'display_order' => $request->display_order ?? 0,
                'is_active' => true,
            ]);

            $motif->load(['parent', 'department']);

            Log::info('Motif d\'appel créé', [
                'motif_id' => $motif->id,
                'label' => $motif->label,
                'created_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $motif,
                'Motif créé avec succès',
                201
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du motif', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la création du motif',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/call-motifs/{id}",
     *     summary="Voir détails d'un motif",
     *     tags={"Admin - Call Motifs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails du motif"),
     *     @OA\Response(response=404, description="Motif non trouvé")
     * )
     */
    public function show($id)
    {
        try {
            $motif = CallMotif::with(['parent', 'children', 'department'])
                              ->find($id);

            if (!$motif) {
                return $this->errorResponse('Motif non trouvé', 404);
            }

            Log::info('Détails motif consultés', [
                'motif_id' => $id,
                'consulted_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $motif,
                'Motif trouvé',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du motif', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération du motif',
                500
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/call-motifs/{id}",
     *     summary="Modifier un motif",
     *     tags={"Admin - Call Motifs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="label", type="string"),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="sla_hours", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Motif modifié")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $motif = CallMotif::find($id);

            if (!$motif) {
                return $this->errorResponse('Motif non trouvé', 404);
            }

            $validator = Validator::make($request->all(), [
                'label' => 'sometimes|string|max:255',
                'category' => 'sometimes|in:info,reclamation,support,commercial,autre',
                'parent_id' => 'nullable|exists:call_motifs,id|not_in:' . $id,
                'department_id' => 'nullable|exists:departments,id',
                'sla_hours' => 'nullable|integer|min:1',
                'suggested_script' => 'nullable|string',
                'display_order' => 'nullable|integer|min:0',
            ], [
                'parent_id.not_in' => 'Un motif ne peut pas être son propre parent',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            $motif->update($request->only([
                'label',
                'category',
                'parent_id',
                'department_id',
                'sla_hours',
                'suggested_script',
                'display_order'
            ]));

            $motif->load(['parent', 'department']);

            Log::info('Motif modifié', [
                'motif_id' => $id,
                'modified_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $motif,
                'Motif modifié avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification du motif', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la modification du motif',
                500
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/call-motifs/{id}",
     *     summary="Supprimer un motif",
     *     tags={"Admin - Call Motifs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Motif supprimé")
     * )
     */
    public function destroy($id)
    {
        try {
            $motif = CallMotif::find($id);

            if (!$motif) {
                return $this->errorResponse('Motif non trouvé', 404);
            }

            $motif->delete();

            Log::info('Motif supprimé', [
                'motif_id' => $id,
                'deleted_by' => Auth::id(),
            ]);

            return $this->successResponse(
                null,
                'Motif supprimé avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du motif', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la suppression du motif',
                500
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/call-motifs/{id}/toggle-status",
     *     summary="Activer/Désactiver un motif",
     *     tags={"Admin - Call Motifs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Statut modifié")
     * )
     */
    public function toggleStatus($id)
    {
        try {
            $motif = CallMotif::find($id);

            if (!$motif) {
                return $this->errorResponse('Motif non trouvé', 404);
            }

            $motif->is_active = !$motif->is_active;
            $motif->save();

            $status = $motif->is_active ? 'activé' : 'désactivé';

            Log::info('Statut du motif modifié', [
                'motif_id' => $id,
                'new_status' => $motif->is_active,
                'changed_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $motif,
                "Motif {$status} avec succès",
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors du changement de statut',
                500
            );
        }
    }
}