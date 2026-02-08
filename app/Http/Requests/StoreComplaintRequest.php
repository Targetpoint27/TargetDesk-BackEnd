<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
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