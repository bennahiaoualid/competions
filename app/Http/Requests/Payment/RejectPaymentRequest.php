<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment\PaymentTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectPaymentRequest extends FormRequest
{
    protected $errorBag = 'rejectPayment';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transaction_id' => 'required|string',
            'observation' => 'required|string|max:1000',
        ];
    }

} 