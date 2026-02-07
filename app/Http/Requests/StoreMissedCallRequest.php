<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMissedCallRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone_number' => 'required|string|max:20',
            'department_id' => 'required|integer|exists:departments,id',
            'caller_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'client_id' => 'nullable|integer|exists:clients,id',
        ];
    }
}