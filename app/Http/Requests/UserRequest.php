<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $userId = $this->route('user')->id ?? null;
        $passwordRule = $userId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed';

        return [
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => $passwordRule,
            'profile' => 'required|exists:profiles,id',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'services' => 'nullable|array',
            'services.*' => 'exists:groups,id',
            'head_services' => 'nullable|array',
            'head_services.*' => 'exists:groups,id',
            'antennes' => 'nullable|array',
            'antennes.*' => 'exists:antennes,id',
            'head_antennes' => 'nullable|array',
            'head_antennes.*' => 'exists:antennes,id',
        ];
    }
}
