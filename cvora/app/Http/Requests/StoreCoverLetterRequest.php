<?php

namespace App\Http\Requests;

use App\Models\CoverLetter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoverLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $letter = $this->route('letter');

        if (! $letter instanceof CoverLetter) {
            return $this->user() !== null;
        }

        return $this->user()?->can('update', $letter) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'offer_excerpt' => ['nullable', 'string', 'max:5000'],
            'tone' => ['required', Rule::in(['classique', 'professionnel', 'direct', 'debutant'])],
            'length' => ['required', Rule::in(['courte', 'moyenne', 'developpee'])],
            'content' => ['required', 'string', 'max:8000'],
            'resume_id' => ['nullable', 'integer'],
            'job_offer_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un titre à la lettre.',
            'content.required' => 'La lettre ne peut pas être vide.',
        ];
    }
}
