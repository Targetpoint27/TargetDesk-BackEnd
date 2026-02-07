<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallNoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'note' => 'required|string',
            'is_important' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'note.required' => 'Le contenu de la note est obligatoire',
        ];
    }
}