<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleCallbackRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}