<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => [
                'required',
                'string',
                'max:255',
                'unique:findings,reference',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
            ],

            'criteria' => [
                'required',
                'string',
            ],

            'effect' => [
                'required',
                'string',
            ],

            'recommendation' => [
                'required',
                'string',
            ],

            'process' => [
                'required',
                'string',
                'max:255',
            ],

            'risk_rating' => [
                'required',
                Rule::in([
                    'LOW',
                    'MEDIUM',
                    'HIGH',
                    'CRITICAL',
                ]),
            ],
            'evidence_description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'evidence_file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt',
                'extensions:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt',
                'max:10240',
            ],
        ];
    }
}
