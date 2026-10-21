<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Support\InputLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $description = $this->input('description');
        $dueDate = $this->input('due_date');

        $this->merge([
            'title' => is_string($this->input('title')) ? trim($this->input('title')) : $this->input('title'),
            'description' => is_string($description) ? (trim($description) === '' ? null : trim($description)) : $description,
            'due_date' => $dueDate === '' ? null : $dueDate,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:'.InputLimits::TITLE],
            'description' => ['nullable', 'string', 'max:'.InputLimits::DESCRIPTION],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.InputLimits::DUE_MIN, 'before_or_equal:'.InputLimits::DUE_MAX],
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
            'priority.required' => 'Choose a priority.',
            'priority.enum' => 'Priority must be low, medium, or high.',
            'status.enum' => 'Status must be pending or completed.',
            'due_date.date_format' => 'Use a real due date.',
            'due_date.after_or_equal' => 'Due dates must fall between 1990 and 2100.',
            'due_date.before_or_equal' => 'Due dates must fall between 1990 and 2100.',
        ];
    }
}
