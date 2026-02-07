<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'type' => 'required|in:entrant,sortant',
            'phone_number' => 'required|string|max:255',
            'caller_name' => 'nullable|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'object' => 'required|string|max:255',
            'summary' => 'required|string',
            'urgency' => 'nullable|in:normal,urgent,critique',
            'client_id' => 'nullable|exists:clients,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'related_to_type' => 'nullable|in:prospect,client,project,command',
            'related_to_id' => 'nullable|integer',
        ];

        if ($this->type === 'sortant') {
            $rules['outbound_reason'] = 'required|in:rappel_client,prospection,suivi_commande,enquete_satisfaction,relance_paiement';
            $rules['call_result'] = 'nullable|in:contacte,messagerie,pas_de_reponse,numero_errone,refuse';
            $rules['call_duration_seconds'] = 'nullable|integer|min:0';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'type.required' => 'Le type d\'appel est obligatoire',
            'type.in' => 'Le type d\'appel doit être "entrant" ou "sortant"',
            'phone_number.required' => 'Le numéro de téléphone est obligatoire',
            'department_id.required' => 'Le département cible est obligatoire',
            'department_id.exists' => 'Le département sélectionné n\'existe pas',
            'object.required' => 'L\'objet de l\'appel est obligatoire',
            'summary.required' => 'Le résumé de l\'appel est obligatoire',
            'urgency.in' => 'Le niveau d\'urgence doit être "normal", "urgent" ou "critique"',
            'client_id.exists' => 'Le client sélectionné n\'existe pas',
            'contact_id.exists' => 'Le contact sélectionné n\'existe pas',
            'outbound_reason.required' => 'Le motif de l\'appel sortant est obligatoire',
        ];
    }
}