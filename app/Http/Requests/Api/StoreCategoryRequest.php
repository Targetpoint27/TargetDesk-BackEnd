<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Autorisation gérée par middleware auth:sanctum
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'unique:categories,name'
            ],
            'description' => [
                'nullable',
                'string',
                'max:500'
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $parent = \App\Models\Category::find($value);
                        if (!$parent || !$parent->is_active) {
                            $fail('La catégorie parent sélectionnée est inactive ou n\'existe pas.');
                        }
                    }
                }
            ],
            'color' => [
                'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/'
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['secteur', 'taille', 'priorite', 'origine', 'personnalisee'])
            ]
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
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'name.unique' => 'Ce nom de catégorie existe déjà.',

            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',

            'parent_id.integer' => 'L\'ID de la catégorie parent doit être un entier.',
            'parent_id.exists' => 'La catégorie parent sélectionnée n\'existe pas.',

            'color.string' => 'La couleur doit être une chaîne de caractères.',
            'color.regex' => 'La couleur doit être au format hexadécimal (#RRGGBB).',

            'type.required' => 'Le type de catégorie est obligatoire.',
            'type.string' => 'Le type doit être une chaîne de caractères.',
            'type.in' => 'Le type doit être l\'un des suivants : secteur, taille, priorite, origine, personnalisee.'
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Nettoyer et formater les données
        $this->merge([
            'name' => trim($this->name),
            'description' => $this->description ? trim($this->description) : null,
            'color' => $this->color ?: '#007bff', // Couleur par défaut
        ]);
    }
}
