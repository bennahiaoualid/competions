<?php

namespace App\Http\Requests\Competition;

use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLevelRequest extends FormRequest
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
    protected $errorBag = 'updateLevel';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Fetch the original competition from the database
        $level = Level::find($this->id);

        $rules = [
            'name' => 'required|string|min:3|max:40',
            'description' => 'nullable|string|min:3|max:100',
            'start_date' => 'required|date_format:Y-m-d H:i',
            'duration' => 'required|integer|min:1',
            'admin_id' => [
                'required',
                'exists:admins,id'
            ],
        ];

        // Apply the after_or_equal:now rule if the start_date has changed
        if ($level && $level->start_date->format('Y-m-d H:i') !== $this->input('start_date')) {
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


}
