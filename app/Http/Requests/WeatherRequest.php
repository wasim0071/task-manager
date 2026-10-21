<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WeatherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'city' => ['required', 'string', 'max:80', "regex:/^[\\p{L}\\p{N}\\s.'’,\\-]+$/u"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'city.required' => 'Enter a city name.',
            'city.regex' => 'Use letters, numbers, spaces, and simple punctuation.',
        ];
    }
}
