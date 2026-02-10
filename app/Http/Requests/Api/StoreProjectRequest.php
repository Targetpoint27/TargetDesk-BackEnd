<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Champs obligatoires selon analyse
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'project_manager_id' => 'required|exists:users,id',
            'start_date' => 'required|date|after_or_equal:today',
            'planned_end_date' => 'required|date|after:start_date',

            // Champs optionnels selon analyse
            'code' => 'nullable|string|unique:projects,code|max:50',
            'client_type' => 'nullable|in:interne,externe',
            'client_id' => 'nullable|required_if:client_type,interne|exists:clients,id',
            'external_client_info' => 'nullable|required_if:client_type,externe|array',
            'external_client_info.name' => 'required_if:client_type,externe|string|max:255',
            'external_client_info.email' => 'nullable|email',
            'external_client_info.phone' => 'nullable|string|max:20',
            'external_client_info.company' => 'nullable|string|max:255',
            'external_client_info.address' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:5000',
            'objectives' => 'nullable|string|max:5000',
            'estimated_budget' => 'nullable|numeric|min:0|max:9999999999.99',
            'status' => 'nullable|in:en_cours,en_attente,en_danger,termine,annule',
            'risk_indicator' => 'nullable|in:low,medium,high',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'nom du projet',
            'department' => 'département',
            'project_manager_id' => 'chef de projet',
            'start_date' => 'date de début',
            'planned_end_date' => 'date de fin prévue',
            'client_type' => 'type de client',
            'client_name' => 'nom du client',
            'description' => 'description',
            'objectives' => 'objectifs',
            'estimated_budget' => 'budget estimé',
            'status' => 'statut initial',
            'risk_indicator' => 'indicateur de risque',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'start_date.after_or_equal' => 'La date de début ne peut pas être antérieure à aujourd\'hui.',
            'planned_end_date.after' => 'La date de fin prévue doit être postérieure à la date de début.',
            'project_manager_id.exists' => 'Le chef de projet sélectionné n\'existe pas.',
            'code.unique' => 'Ce code projet est déjà utilisé.',
        ];
    }
}
