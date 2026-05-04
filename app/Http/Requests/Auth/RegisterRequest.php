<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email|max:255',
            'password'        => 'required|string|min:8|confirmed',
            'phone'           => 'nullable|string|max:20',
            'education_level' => 'nullable|in:diploma,bachelor,master,phd,other',
            'field_of_study'  => 'nullable|string|max:255',
            'country'         => 'nullable|string|max:100',
        ];
    }
}
