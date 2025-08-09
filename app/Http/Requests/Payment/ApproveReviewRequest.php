<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ApproveReviewRequest extends FormRequest
{
    protected $errorBag = 'approveReview';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_id' => ['required', 'integer', 'exists:payment_review_requests,id'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }
} 