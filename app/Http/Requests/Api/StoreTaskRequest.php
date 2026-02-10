<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'required|array|min:1',
            'assigned_to.*' => 'integer|exists:users,id',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:basse,normale,haute,critique',
            'status' => 'nullable|in:a_faire,en_cours,bloque,test,termine',
            'due_date' => 'nullable|date|after:today',
            'estimated_hours' => 'nullable|numeric|min:0',
            'type' => 'nullable|in:dev,design,test,analyse,autre',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'parent_task_id' => 'nullable|integer|exists:tasks,id'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Le titre de la tâche est obligatoire',
            'project_id.required' => 'Le projet est obligatoire',
            'assigned_to.required' => 'Au moins un assigné est obligatoire',
            'assigned_to.*.exists' => 'Un des utilisateurs assignés n\'existe pas',
            'due_date.after' => 'La date d\'échéance doit être dans le futur',
        ];
    }
}
