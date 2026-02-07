<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallbackResultRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // The result of the phone call
            'call_result' => 'required|in:contacte,messagerie,pas_de_reponse,numero_errone,refuse',
            'summary' => 'required|string',
            
            // Optional: If they want to "Snooze" (Reschedule immediately)
            'reschedule_date' => 'nullable|date|after_or_equal:today',
            'reschedule_time' => 'nullable|required_with:reschedule_date|date_format:H:i',
        ];
    }
}