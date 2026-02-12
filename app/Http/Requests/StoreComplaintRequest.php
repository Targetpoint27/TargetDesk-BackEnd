<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 * title="StoreComplaintRequest",
 * description="Requête pour la création d'une nouvelle réclamation",
 * type="object",
 * required={"call_id", "category", "severity", "description"}
 * )
 */
class StoreComplaintRequest extends FormRequest
{
    /**
     * @OA\Property(property="call_id", type="integer", example=4, description="ID de l'appel lié")
     * @OA\Property(property="client_id", type="integer", nullable=true, example=1, description="ID du client")
     * @OA\Property(property="category", type="string", enum={"produit_defectueux", "service_insatisfaisant", "livraison_retard", "facturation_erronee", "comportement_personnel", "autre"}, example="service_insatisfaisant")
     * @OA\Property(property="severity", type="string", enum={"faible", "moyen", "eleve", "critique"}, example="eleve")
     * @OA\Property(property="description", type="string", example="Le client n'a pas aimé son produit.", description="Description détaillée du litige")
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'call_id' => 'required|exists:calls,id',
            'client_id' => 'nullable|exists:clients,id',
            'category' => 'required|in:produit_defectueux,service_insatisfaisant,livraison_retard,facturation_erronee,comportement_personnel,autre',
            'severity' => 'required|in:faible,moyen,eleve,critique',
            'description' => 'required|string|min:10',
        ];
    }
}