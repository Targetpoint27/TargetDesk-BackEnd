<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkClientRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id'
        ];
    }
}