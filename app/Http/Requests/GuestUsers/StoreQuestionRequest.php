<?php

namespace App\Http\Requests\GuestUsers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'createQuestion';

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
            'question_text' => 'required|string|min:3|max:400',
            'duration' => 'required|integer|min:30',
            'score' => 'required|integer|min:1',
            'choice' => 'array|min:2|max:5',
            'choice.*' => 'required|string|min:3|max:400',
            'explanation' => 'nullable|string|min:3|max:400',
            'txt_direction' => [
                'required',
                Rule::in(['ltr', 'rtl']),
            ],
        ];
    }
}
