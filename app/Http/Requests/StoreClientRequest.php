<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:particulier,entreprise',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'siret' => 'nullable|string|size:14|unique:clients,siret',
            'sector' => 'nullable|string|max:100',
            'website' => 'nullable|url',
            'notes' => 'nullable|string'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Le nom/raison sociale est requis',
            'type.required' => 'Le type est requis',
            'type.in' => 'Le type doit être "particulier" ou "entreprise"',
            'email.required' => 'L\'email principal est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'siret.size' => 'Le SIRET doit contenir exactement 14 caractères',
            'siret.unique' => 'Ce SIRET est déjà utilisé',
            'website.url' => 'Le site web doit être une URL valide'
        ];
    }
}
