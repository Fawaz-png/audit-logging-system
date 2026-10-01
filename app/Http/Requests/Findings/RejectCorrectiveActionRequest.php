<?php

namespace App\Http\Requests\Findings;

use Illuminate\Foundation\Http\FormRequest;

class RejectCorrectiveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'max:10000',
            ],
        ];
    }
}
