<?php

namespace App\Http\Requests\Payment;

use App\Enums\UserTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateCoinPricingRequest extends FormRequest
{
    protected $errorBag = 'createCoinPricing';

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
            'user_type' => [
                'required', 'string',
                Rule::in(UserTypeEnum::values()),
            ],
            'base_amount' => ['required', 'integer', 'min:10', 'max:10000'],
            'base_coins' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

}