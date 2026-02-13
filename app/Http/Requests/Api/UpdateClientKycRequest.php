<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientKycRequest extends FormRequest
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
            // Champs existants (optionnels lors des updates)
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|nullable|string|max:100',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string',
            'siret' => 'sometimes|nullable|string|max:14',
            'sector' => 'sometimes|nullable|string|max:255',
            'website' => 'sometimes|nullable|url|max:255',
            'notes' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',

            // Nouveaux champs KYC (tous optionnels)
            'brand_workshop' => 'sometimes|nullable|string|max:255',
            'legal_form' => 'sometimes|nullable|string|max:255',
            'legal_representative_first_name' => 'sometimes|nullable|string|max:255',
            'legal_representative_last_name' => 'sometimes|nullable|string|max:255',
            'beneficial_owner_first_name' => 'sometimes|nullable|string|max:255',
            'beneficial_owner_last_name' => 'sometimes|nullable|string|max:255',
            'bank' => 'sometimes|nullable|string|max:255',
            'bank_account_type' => 'sometimes|nullable|string|max:255',
            'payment_moment' => 'sometimes|nullable|string|max:255',
            'payment_in_foreign_currency' => 'sometimes|boolean',
            'has_bank_identity_statement' => 'sometimes|boolean'
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
            'name.required' => 'Le nom du client est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'website.url' => 'Le site web doit être une URL valide.',
            'siret.max' => 'Le numéro SIRET ne peut pas dépasser 14 caractères.',
            'payment_in_foreign_currency.boolean' => 'Le champ "paiement en devise" doit être true ou false.',
            'has_bank_identity_statement.boolean' => 'Le champ "relevé d\'identité bancaire" doit être true ou false.'
        ];
    }
}
