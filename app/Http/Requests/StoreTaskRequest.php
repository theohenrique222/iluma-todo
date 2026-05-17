<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', Rule::enum(TaskPriority::class)],
            'project_id' => ['nullable', 'exists:projects,id'],
        ];
    }
}
