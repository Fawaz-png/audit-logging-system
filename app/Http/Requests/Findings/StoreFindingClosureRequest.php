<?php

namespace App\Http\Requests\Findings;

use Illuminate\Foundation\Http\FormRequest;

class StoreFindingClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'max:10000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt',
                'extensions:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt',
                'max:10240',
            ],
        ];
    }
}
