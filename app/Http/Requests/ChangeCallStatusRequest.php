<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeCallStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|in:a_traiter,en_cours,en_attente,a_rappeler,resolu,cloture,annule',
            'comment' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le nouveau statut est obligatoire',
            'status.in' => 'Le statut sélectionné n\'est pas valide',
            'comment.max' => 'Le commentaire ne peut pas dépasser 500 caractères',
        ];
    }
}