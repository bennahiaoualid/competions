<?php

namespace App\Http\Requests\Admin;

use App\Models\Competition\Response;
use Illuminate\Foundation\Http\FormRequest;

class AuditUserResponsesScoreRequest extends FormRequest
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
    protected $errorBag = 'auditUserResponses';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scores' => 'required|array',
            'scores.*' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
