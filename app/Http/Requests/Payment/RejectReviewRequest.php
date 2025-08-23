<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class RejectReviewRequest extends FormRequest
{
    protected $errorBag = 'rejectReview';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_id' => ['required', 'integer'],
            'observation' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
} 