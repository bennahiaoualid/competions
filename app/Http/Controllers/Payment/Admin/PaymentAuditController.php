<?php
namespace App\Http\Controllers\Payment\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentAuditLog;
use App\Contracts\FlasherInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentAuditController extends Controller
{
    public function __construct(protected FlasherInterface $flasher) {}

    public function index(): View
    {
        return view('pages.admin.payment.audit_logs');
    }

    public function delete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audit_log_id' => ['required', 'integer', 'exists:payment_audit_logs,id'],
        ]);

        // Deletion disabled: inform user and return
        $this->flasher->warning(__('payment.audit.messages.deletion_disabled_flash'));
        return back();

        // If later enabling deletion:
        // PaymentAuditLog::where('id', $validated['audit_log_id'])->delete();
        // $this->flasher->success(__('payment.audit.messages.deleted_successfully'));
        // return back();
    }
} 