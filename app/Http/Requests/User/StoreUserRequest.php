<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('add user');
    }

    protected $errorBag = 'createUser';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:60',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:admins,email','unique:users,email'],
            'birthdate' => 'date',
            'gender' => [
                'required',
                Rule::in(['male', 'female']),
            ],
            'password' => 'required|min:8',
        ];
    }
    protected function passedValidation()
    {
        $this->merge([
            // Transforming the date to mysql format
            'birthdate' =>  date("Y-m-d", strtotime($this->birthdate)),
        ]);
    }
}
