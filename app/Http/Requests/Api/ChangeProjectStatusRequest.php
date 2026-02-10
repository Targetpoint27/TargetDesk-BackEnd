<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

class ChangeProjectStatusRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'status' => 'required|in:en_cours,en_attente,en_danger,termine,annule',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function attributes()
    {
        return [
            'status' => 'statut',
            'comment' => 'commentaire',
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
            'comment.max' => 'Le commentaire ne peut pas dépasser 1000 caractères.',
        ];
    }
}
