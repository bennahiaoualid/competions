<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return  $this->user()->can('update user');
    }

    protected $errorBag = 'updateUser';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:60',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:admins,email','unique:users,email,'.$this->id],
        ];
    }
}
