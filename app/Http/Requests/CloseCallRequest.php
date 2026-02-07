<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCallRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'resolution_summary' => 'required|string',
            'final_result' => 'required|in:resolu_satisfait,resolu_insatisfait,transfere,non_resolu',
        ];
    }

    public function messages()
    {
        return [
            'resolution_summary.required' => 'Le résumé de résolution est obligatoire',
            'final_result.required' => 'Le résultat final est obligatoire',
            'final_result.in' => 'Le résultat final doit être: résolu satisfait, résolu insatisfait, transféré ou non résolu',
        ];
    }
}