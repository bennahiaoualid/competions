<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment\PaymentTransaction;

class PaymentProofController extends Controller
{
    public function show(PaymentTransaction $transaction)
    {
        $user = Auth::user();

        // Authorization: owner OR accountant (manage payment)
        if ($transaction->payable_id !== $user->id && !$user->can('manage payment')) {
            abort(403, 'Unauthorized');
        }

        if (!Storage::disk('local')->exists($transaction->proof_image_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($transaction->proof_image_path));
    }
}