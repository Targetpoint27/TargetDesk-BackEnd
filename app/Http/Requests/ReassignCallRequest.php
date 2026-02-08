<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReassignCallRequest extends FormRequest
{
    public function authorize()
    {
        return true; // In real app: check if user is supervisor
    }

    public function rules()
    {
        return [
            'new_agent_id' => 'required|exists:users,id',
            'reason' => 'nullable|string'
        ];
    }
}