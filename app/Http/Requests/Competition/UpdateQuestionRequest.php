<?php

namespace App\Http\Requests\Competition;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected $errorBag = 'updateQuestion';

    /**
     * @param string $errorBag
     */
    public function setErrorBag(string $errorBag): void
    {
        $this->errorBag = $errorBag ;
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
            'max_score' => 'required|integer|min:1',
        ];
    }
    public function withValidator($validator)
    {
        $this->setErrorBag("updateQuestion".$this->input("id"));
        //dd($this->errorBag);
    }

}
