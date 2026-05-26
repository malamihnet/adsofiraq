<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Person|null $person */
        $person = $this->route('person');

        $photoRule = $this->isMethod('post')
            ? ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']
            : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

        return [
            'photo' => $photoRule,
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'official_profile_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'work_1' => ['nullable', 'string', 'max:255'],
            'work_2' => ['nullable', 'string', 'max:255'],
            'work_3' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'submission_notes' => ['nullable', 'string', 'max:1000'],
            'is_verified' => ['nullable', 'boolean'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}
