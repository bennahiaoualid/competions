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
            'review_id' => ['required', 'integer'],
            'observation' => ['nullable', 'string', 'min:3', 'max:1000'],
        ];
    }
} 