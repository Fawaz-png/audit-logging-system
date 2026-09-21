<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuditRequest extends FormRequest
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

            'objective' => [
                'required',
                'string',
            ],

            'scope' => [
                'required',
                'string',
            ],

            'department_id' => [
                'required',
                'integer',
                'exists:departments,id',
            ],

            'audit_mode' => [
                'required',
                Rule::in([
                    'ONSITE',
                    'REMOTE',
                    'HYBRID',
                ]),
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'methodology' => [
                'required',
                Rule::in([
                    'RISK_BASED',
                    'COMPLIANCE_BASED',
                    'HYBRID',
                ]),
            ],

            'previous_audit_reference' => [
                'nullable',
                'string',
                'max:50',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'lead_auditor_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'supporting_auditor_ids' => [
                'nullable',
                'array',
            ],

            'supporting_auditor_ids.*' => [
                'integer',
                'exists:users,id',
                'distinct',
                Rule::notIn([
                    $this->input('lead_auditor_id'),
                ]),
            ],

            'auditee_ids' => [
                'nullable',
                'array',
            ],

            'auditee_ids.*' => [
                'integer',
                'exists:users,id',
                'distinct',
                Rule::notIn(array_merge(
                    [$this->input('lead_auditor_id')],
                    $this->input('supporting_auditor_ids', [])
                )),
            ],
        ];
    }
}