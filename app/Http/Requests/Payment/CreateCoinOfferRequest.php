<?php

namespace App\Http\Requests\Payment;

use App\Traits\TimeManipulation;
use Illuminate\Foundation\Http\FormRequest;

class CreateCoinOfferRequest extends FormRequest
{
    use TimeManipulation;

    protected $errorBag = 'createCoinOffer';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'coin_pricing_id' => ['required', 'exists:coin_pricing,id'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_percentage' => ['required', 'integer', 'min:5', 'max:90'],
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
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

        if ($this->end_date) {
            $this->merge([
                'end_date' => $this->convertDateToUtc($this->end_date),
            ]);
        }
    }
} 