<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Support\InputLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('search'))) {
            $this->merge([
                'search' => trim($this->input('search')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:'.InputLimits::SEARCH],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'sort' => ['nullable', 'in:created_at,due_date,priority,title'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.InputLimits::PAGE_MAX],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.InputLimits::PER_PAGE_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' => 'Search must be '.InputLimits::SEARCH.' characters or fewer.',
            'status.enum' => 'Status must be pending or completed.',
            'priority.enum' => 'Priority must be low, medium, or high.',
            'sort.in' => 'That sort field is not available.',
            'direction.in' => 'Sort direction must be asc or desc.',
            'page.integer' => 'Page must be a whole number.',
            'page.max' => 'That page number is too large.',
            'per_page.max' => 'Ask for '.InputLimits::PER_PAGE_MAX.' tasks per page at most.',
        ];
    }
}
