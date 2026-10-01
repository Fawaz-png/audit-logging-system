<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }
}
