<?php

namespace App\Http\Requests\Competition;

use App\Traits\TimeManipulation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FilterCompetitionRequest extends FormRequest
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
    protected $errorBag = 'filterCompetitions';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|min:3|max:40',
            'start_date_from' => 'nullable|date_format:Y-m-d',
            'start_date_to' => 'nullable|date_format:Y-m-d',
            'age_start' => 'nullable|integer|min:6',
            'age_end' => 'nullable|integer|min:6',
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
        if ($this->start_date_to) {
            $this->merge([
                'start_date_to' => $this->convertDateToUtc($this->start_date_to,"Y-m-d"),
            ]);
        }
        if ($this->start_date_from) {
            $this->merge([
                'start_date_from' => $this->convertDateToUtc($this->start_date_from, "Y-m-d"),
            ]);
        }
    }
}
