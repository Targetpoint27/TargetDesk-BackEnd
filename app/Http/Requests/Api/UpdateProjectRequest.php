<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'objectives' => 'nullable|string|max:5000',
            'estimated_budget' => 'nullable|numeric|min:0|max:9999999999.99',
            'planned_end_date' => 'sometimes|required|date|after:start_date',
            'project_manager_id' => 'sometimes|required|exists:users,id',
            'department' => 'sometimes|required|string|max:255',

            'client_type' => 'nullable|in:interne,externe',
            'client_id' => 'nullable|required_if:client_type,interne|exists:clients,id',
            'external_client_info' => 'nullable|required_if:client_type,externe|array',
            'external_client_info.name' => 'required_if:client_type,externe|string|max:255',
            'external_client_info.email' => 'nullable|email',
            'external_client_info.phone' => 'nullable|string|max:20',
            'external_client_info.company' => 'nullable|string|max:255',
            'external_client_info.address' => 'nullable|string|max:500',

            'risk_indicator' => 'nullable|in:low,medium,high',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'nom du projet',
            'description' => 'description',
            'objectives' => 'objectifs',
            'estimated_budget' => 'budget estimé',
            'planned_end_date' => 'date de fin prévue',
            'project_manager_id' => 'chef de projet',
            'department' => 'département',
            'client_type' => 'type de client',
            'external_client_info.name' => 'nom du client externe',
            'external_client_info.email' => 'email du client externe',
            'external_client_info.phone' => 'téléphone du client externe',
            'external_client_info.company' => 'entreprise du client externe',
            'external_client_info.address' => 'adresse du client externe',
            'risk_indicator' => 'indicateur de risque',
        ];
    }

    public function messages()
    {
        return [
            'planned_end_date.after' => 'La date de fin prévue doit être postérieure à la date de début.',
            'project_manager_id.exists' => 'Le chef de projet sélectionné n\'existe pas.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'client_id.required_if' => 'Vous devez sélectionner un client interne.',
            'external_client_info.required_if' => 'Vous devez fournir les informations du client externe.',
            'external_client_info.name.required_if' => 'Le nom du client externe est obligatoire.',
        ];
    }
}
