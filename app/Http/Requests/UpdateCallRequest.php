<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCallRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'type' => 'sometimes|in:entrant,sortant',
            'phone_number' => 'sometimes|string|max:255',
            'caller_name' => 'nullable|string|max:255',
            'department_id' => 'sometimes|exists:departments,id',
            'object' => 'sometimes|string|max:255',
            'summary' => 'sometimes|string',
            'urgency' => 'nullable|in:normal,urgent,critique',
            'status' => 'sometimes|in:a_traiter,en_cours,en_attente,a_rappeler,resolu,cloture,annule',
            'client_id' => 'nullable|exists:clients,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'related_to_type' => 'nullable|in:prospect,client,project,command',
            'related_to_id' => 'nullable|integer',
            'assigned_to' => 'nullable|exists:users,id',
        ];

        if ($this->type === 'sortant') {
            $rules['outbound_reason'] = 'sometimes|in:rappel_client,prospection,suivi_commande,enquete_satisfaction,relance_paiement';
            $rules['call_result'] = 'nullable|in:contacte,messagerie,pas_de_reponse,numero_errone,refuse';
            $rules['call_duration_seconds'] = 'nullable|integer|min:0';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'type.in' => 'Le type d\'appel doit être "entrant" ou "sortant"',
            'department_id.exists' => 'Le département sélectionné n\'existe pas',
            'urgency.in' => 'Le niveau d\'urgence doit être "normal", "urgent" ou "critique"',
            'status.in' => 'Le statut sélectionné n\'est pas valide',
            'client_id.exists' => 'Le client sélectionné n\'existe pas',
            'contact_id.exists' => 'Le contact sélectionné n\'existe pas',
            'assigned_to.exists' => 'L\'agent assigné n\'existe pas',
        ];
    }
}