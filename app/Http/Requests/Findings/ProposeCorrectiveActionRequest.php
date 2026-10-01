<?php

namespace App\Http\Requests\Findings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ProposeCorrectiveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'responsible_department_id' => [
                'required',
                'integer',
                'exists:departments,id',
            ],

            'responsible_user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'action' => [
                'required',
                'string',
                'max:10000',
            ],

            'proposed_due_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $departmentId = $this->input('responsible_department_id');
            $userId = $this->input('responsible_user_id');

            if (! $departmentId || ! $userId) {
                return;
            }

            $belongsToDepartment = User::query()
                ->whereKey($userId)
                ->where('department_id', $departmentId)
                ->exists();

            if (! $belongsToDepartment) {
                $validator->errors()->add(
                    'responsible_user_id',
                    'The responsible person must belong to the selected department.'
                );
            }
        });
    }
}
