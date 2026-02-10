<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|in:basse,normale,haute,critique',
            'status' => 'sometimes|in:a_faire,en_cours,bloque,test,termine',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'type' => 'sometimes|in:dev,design,test,analyse,autre',
            'assigned_to' => 'sometimes|array',
            'assigned_to.*' => 'integer|exists:users,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ];
    }
}
