<?php

namespace App\Http\Requests\Admin;

use App\Traits\RoleManipulation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    use RoleManipulation;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'createAdmin';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:60',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:admins,email'],
            'birthdate' => 'date',
            'gender' => [
                'required',
                Rule::in(['male', 'female']),
            ],
            'role' => [
                'required',
                Rule::in($this->possibleRolesIds(Auth::user()->getRoleNames()->first())),
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
