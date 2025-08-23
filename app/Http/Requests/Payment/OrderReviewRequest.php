<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class OrderReviewRequest extends FormRequest
{
    protected $errorBag = 'orderReview';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
} 