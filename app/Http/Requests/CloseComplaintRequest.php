<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseComplaintRequest extends FormRequest
{
    public function authorize()
    {
        // In a real app, you would check if user is a Supervisor here.
        // return $this->user()->hasRole('supervisor');
        return true; 
    }

    public function rules()
    {
        return [
            'closing_comment' => 'nullable|string'
        ];
    }
}