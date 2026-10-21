<?php

namespace App\Http\Requests;

use App\Support\InputLimits;
use Illuminate\Foundation\Http\FormRequest;

class WeatherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('city'))) {
            $this->merge([
                'city' => trim($this->input('city')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'city' => ['required', 'string', 'min:1', 'max:'.InputLimits::CITY, "regex:/^[\\p{L}\\p{N}\\s.'’,\\-]+$/u"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'city.required' => 'Enter a city name.',
            'city.min' => 'Enter a city name.',
            'city.max' => 'City names must be '.InputLimits::CITY.' characters or fewer.',
            'city.regex' => 'Use letters, numbers, spaces, and simple punctuation.',
        ];
    }
}
