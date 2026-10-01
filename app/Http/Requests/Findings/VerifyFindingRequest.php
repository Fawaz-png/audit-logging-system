<?php

namespace App\Http\Requests\Findings;

use Illuminate\Foundation\Http\FormRequest;

class VerifyFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => [
                'required',
                'string',
                'max:10000',
            ],
        ];
    }
}
