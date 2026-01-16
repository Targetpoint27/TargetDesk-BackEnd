<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ContactEmail;
use App\Models\ContactPhone;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $clientId = $this->route('clientId');
        $supplierId = $this->route('supplierId');

        return [
            'civility' => 'nullable|in:M.,Mme,Dr.,Prof.,Maître',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'function' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'is_primary' => 'boolean',

            // Emails (au moins un requis)
            'emails' => 'required|array|min:1',
            'emails.*.email' => [
                'required',
                'email',
                'max:255',
                function ($attribute, $value, $fail) use ($clientId, $supplierId) {
                    // Vérifier l'unicité de l'email dans le client ou fournisseur
                    if ($clientId) {
                        $emailExists = ContactEmail::whereHas('contact', function ($query) use ($clientId) {
                            $query->where('client_id', $clientId)->where('is_active', true);
                        })->where('email', $value)->exists();

                        if ($emailExists) {
                            $fail('Cet email est déjà utilisé par un autre contact de ce client.');
                        }
                    } elseif ($supplierId) {
                        $emailExists = ContactEmail::whereHas('contact', function ($query) use ($supplierId) {
                            $query->where('supplier_id', $supplierId)->where('is_active', true);
                        })->where('email', $value)->exists();

                        if ($emailExists) {
                            $fail('Cet email est déjà utilisé par un autre contact de ce fournisseur.');
                        }
                    }
                }
            ],
            'emails.*.type' => 'required|in:' . implode(',', ContactEmail::getTypes()),
            'emails.*.is_primary' => 'boolean',

            // Téléphones (optionnels)
            'phones' => 'nullable|array',
            'phones.*.phone' => 'required|string|max:20',
            'phones.*.type' => 'required|in:' . implode(',', ContactPhone::getTypes()),
            'phones.*.is_primary' => 'boolean',
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
            'first_name.required' => 'Le prénom est requis',
            'last_name.required' => 'Le nom est requis',
            'civility.in' => 'La civilité doit être l\'une des valeurs suivantes : M., Mme, Dr., Prof., Maître',

            'emails.required' => 'Au moins un email est requis',
            'emails.min' => 'Au moins un email est requis',
            'emails.*.email.required' => 'L\'email est requis',
            'emails.*.email.email' => 'L\'email doit être valide',
            'emails.*.type.required' => 'Le type d\'email est requis',
            'emails.*.type.in' => 'Le type d\'email doit être professionnel ou personnel',

            'phones.*.phone.required' => 'Le numéro de téléphone est requis',
            'phones.*.type.required' => 'Le type de téléphone est requis',
            'phones.*.type.in' => 'Le type de téléphone doit être bureau, mobile, fax ou autre',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier qu'il y a au maximum un email principal
            $emails = $this->input('emails', []);
            $primaryEmails = collect($emails)->where('is_primary', true);

            if ($primaryEmails->count() > 1) {
                $validator->errors()->add('emails', 'Un seul email peut être défini comme principal');
            }

            // Vérifier qu'il y a au maximum un téléphone principal
            $phones = $this->input('phones', []);
            $primaryPhones = collect($phones)->where('is_primary', true);

            if ($primaryPhones->count() > 1) {
                $validator->errors()->add('phones', 'Un seul téléphone peut être défini comme principal');
            }

            // Si aucun email n'est marqué comme principal, marquer le premier
            if ($primaryEmails->count() === 0 && count($emails) > 0) {
                $data = $this->all();
                $data['emails'][0]['is_primary'] = true;
                $this->merge($data);
            }

            // Si aucun téléphone n'est marqué comme principal et qu'il y en a, marquer le premier
            if ($primaryPhones->count() === 0 && count($phones) > 0) {
                $data = $this->all();
                $data['phones'][0]['is_primary'] = true;
                $this->merge($data);
            }
        });
    }
}
