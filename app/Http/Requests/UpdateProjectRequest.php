<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_project' => 'required|string|max:255',
            'client' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'project_value' => 'nullable|string',
            'budget_period' => ['nullable', Rule::in(['total', 'monthly', 'daily'])],
            'daily_budget' => 'nullable|string',
            'monthly_budget' => 'nullable|string',
            'business_type' => 'nullable|string|max:50',
            'cogs_ratio_alert' => 'nullable|numeric|min:0|max:100',
            'lock_closed_days' => 'nullable|boolean',
        ];
    }
}
