<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'role_id' => 'sometimes|exists:roles,id',
            'status' => 'sometimes|in:active,inactive',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'first_name.string' => 'Le prénom doit être une chaîne de caractères',
            'last_name.string' => 'Le nom doit être une chaîne de caractères',
            'role_id.exists' => 'Le rôle sélectionné n\'existe pas',
            'status.in' => 'Le statut doit être "active" ou "inactive"',
        ];
    }
}
