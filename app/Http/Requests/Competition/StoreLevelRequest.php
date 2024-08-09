<?php

namespace App\Http\Requests\Competition;

use App\Traits\TimeManipulation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreLevelRequest extends FormRequest
{
    use TimeManipulation;
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
    protected $errorBag = 'createLevel';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:40',
            'description' => 'nullable|string|min:3|max:100',
            'start_date' => 'required|date_format:Y-m-d H:i|after_or_equal:now',
            'duration' => 'required|integer|min:1',
            'questions_number' => 'required|integer|min:1',
            'admin_id' => 'exists:admins,id',
        ];
    }

    protected function prepareForValidation()
    {

        // Convert the start_date to UTC before validation
        if ($this->start_date) {
            $this->merge([
                'start_date' => $this->convertDateToUtc($this->start_date),
            ]);
        }
    }


}
