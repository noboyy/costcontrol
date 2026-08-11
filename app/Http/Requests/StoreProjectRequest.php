<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in([app()->make(\App\Models\Project::class)::MODE_PROJECT, app()->make(\App\Models\Project::class)::MODE_UMKM])],
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
            'seed_template' => 'nullable|boolean',
        ];
    }
}
