<?php

namespace App\Http\Requests\Findings;

use Illuminate\Foundation\Http\FormRequest;

class ReviewFindingClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
