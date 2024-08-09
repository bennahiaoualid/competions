<?php

namespace App\Http\Requests\Competition;

use App\Models\Competition\Competition;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompetitionRequest extends FormRequest
{
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
    protected $errorBag = 'updateCompetition';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Fetch the original competition from the database
        $competition = Competition::find($this->id);

        $rules = [
            'start_date' => 'required|date_format:Y-m-d H:i',
            'age_start' => 'required|integer|min:6',
            'age_end' => 'required|integer|min:6',
        ];

        // Apply the after_or_equal:now rule if the start_date has changed
        if ($competition && $competition->start_date->format('Y-m-d H:i') !== $this->input('start_date')) {
            $rules['start_date'] .= '|after_or_equal:now';
        }

        return $rules;
    }

    protected function prepareForValidation()
    {
        // Convert the start_date to UTC before validation
        if ($this->has('start_date') && session()->has('timezone')) {
            $timezone = session()->get('timezone');
            $startDate = Carbon::createFromFormat('Y-m-d H:i', $this->input('start_date'), $timezone);
            $this->merge([
                'start_date' => $startDate->setTimezone('UTC')->format('Y-m-d H:i'),
            ]);
        }
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
}
