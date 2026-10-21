<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Support\InputLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach (['title', 'description', 'due_date'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = $this->input($field);

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '' && in_array($field, ['description', 'due_date'], true)) {
                $value = null;
            }

            $payload[$field] = $value;
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:1', 'max:'.InputLimits::TITLE],
            'description' => ['sometimes', 'nullable', 'string', 'max:'.InputLimits::DESCRIPTION],
            'priority' => ['sometimes', 'required', Rule::enum(TaskPriority::class)],
            'status' => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:'.InputLimits::DUE_MIN, 'before_or_equal:'.InputLimits::DUE_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Give the task a title.',
            'title.min' => 'Give the task a title.',
            'title.max' => 'Titles must be '.InputLimits::TITLE.' characters or fewer.',
            'description.max' => 'Descriptions must be '.InputLimits::DESCRIPTION.' characters or fewer.',
            'priority.enum' => 'Priority must be low, medium, or high.',
            'status.enum' => 'Status must be pending or completed.',
            'due_date.date_format' => 'Use a real due date.',
            'due_date.after_or_equal' => 'Due dates must fall between 1990 and 2100.',
            'due_date.before_or_equal' => 'Due dates must fall between 1990 and 2100.',
        ];
    }
}
