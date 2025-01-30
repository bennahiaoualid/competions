<?php

namespace App\Http\Requests\GuestUsers;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'storeResponse';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'choice_id' => 'integer|exists:choices,id',
            'question_id' => 'integer|exists:global_questions,id'
        ];
    }
}
