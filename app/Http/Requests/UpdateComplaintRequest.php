<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Workflow Status
            'status' => 'nullable|in:ouverte,en_analyse,en_attente_client,en_attente_interne',
            
            // Assignment
            'assigned_to' => 'nullable|exists:users,id',
            
            // Processing Details (US-CC-025)
            'root_cause' => 'nullable|string',
            'actions_taken' => 'nullable|string',
            'proposed_solution' => 'nullable|string',
            'compensation_details' => 'nullable|string',
            'prevention_measures' => 'nullable|string',
        ];
    }
}