<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin\Admin;
use App\Traits\RoleManipulation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    use RoleManipulation;
    protected $errorBag = 'createAdmin';
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'name' => 'required|string|min:3|max:60',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:admins,email,'. $this->id],
            'role' => [
                'required',
                Rule::in($this->possibleRolesIds(Auth::user()->getRoleNames()->first())),
            ],
        ];
    }
}
