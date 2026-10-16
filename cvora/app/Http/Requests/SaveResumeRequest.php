<?php

namespace App\Http\Requests;

use App\Models\Resume;
use App\Services\Resumes\ResumeContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $resume = $this->route('resume');

        if (! $resume instanceof Resume) {
            return $this->user() !== null;
        }

        return $this->user()?->can('update', $resume) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'template_slug' => ['required', 'string', 'max:40'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'record_version' => ['sometimes', 'boolean'],
            ...ResumeContent::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un titre au CV.',
            'accent_color.regex' => 'La couleur doit être un code hexadécimal.',
        ];
    }
}
