<?php

namespace App\Http\Requests\Findings;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCorrectiveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agreed_due_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }
}
