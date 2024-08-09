<?php

namespace App\Http\Requests\Competition;

use App\Traits\TimeManipulation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCompetitionRequest extends FormRequest
{
    use TimeManipulation;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can("add competition");
    }

    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'createCompetition';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:40',
            'description' => 'nullable|string|min:3|max:100',
            'start_date' => 'required|date_format:Y-m-d H:i|after_or_equal:now',
            'age_start' => 'required|integer|min:6',
            'age_end' => 'required|integer|min:6',
            'levels_number' => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('age_end') < $this->input('age_start')) {
                $validator->errors()->add('age_end', trans('validation.custom.age_minmax_validation',
                    ['attribute' => trans('validation.attributes.age_end'), 'other' => trans('validation.attributes.age_start')]));
            }
        });
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
