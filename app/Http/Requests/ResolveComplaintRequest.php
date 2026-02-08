<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveComplaintRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // US-CC-026: Mandatory fields for resolution
            'resolution_summary' => 'required|string|min:10',
            'client_satisfaction' => 'required|in:satisfait,partiellement_satisfait,non_satisfait',
            'compensation_details' => 'nullable|string',
        ];
    }
}