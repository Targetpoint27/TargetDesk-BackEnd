<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('categories', 'name')->ignore($categoryId)
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500'
            ],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value) {
                        // Vérifier que le parent existe et est actif
                        $parent = \App\Models\Category::find($value);
                        if (!$parent || !$parent->is_active) {
                            $fail('La catégorie parent sélectionnée est inactive ou n\'existe pas.');
                        }

                        // Vérifier qu'on n'essaie pas de se définir comme son propre parent
                        if ($value == $categoryId) {
                            $fail('Une catégorie ne peut pas être son propre parent.');
                        }

                        // Vérifier les références circulaires
                        $currentCategory = \App\Models\Category::find($categoryId);
                        if ($currentCategory && !$currentCategory->canHaveParent($parent)) {
                            $fail('Référence circulaire détectée. Une catégorie ne peut pas être parent de ses ancêtres.');
                        }
                    }
                }
            ],
            'color' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/'
            ],
            'type' => [
                'sometimes',
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
        // Nettoyer et formater les données seulement si elles sont présentes
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim($this->name);
        }

        if ($this->has('description')) {
            $data['description'] = $this->description ? trim($this->description) : null;
        }

        if ($this->has('color') && $this->color) {
            $data['color'] = $this->color;
        }

        $this->merge($data);
    }
}
