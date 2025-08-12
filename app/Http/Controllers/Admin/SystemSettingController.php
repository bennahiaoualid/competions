<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FlasherInterface;
use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(
        private SystemSettingService $systemSettingService,
        private FlasherInterface $flasher
    ) {
        //
    }

    /**
     * Display all system settings
     */
    public function index(): View
    {
        $allSettings = $this->systemSettingService->getAllSettings();
        
        // Filter locally instead of calling separate methods
        $paymentSettings = $this->systemSettingService->filterSettingsByCategory($allSettings, 'payment');
        $systemSettings = $this->systemSettingService->filterSettingsByCategory($allSettings, 'system');
        $notificationSettings = $this->systemSettingService->filterSettingsByCategory($allSettings, 'notifications');
        
        return view('pages.admin.system.settings.index', compact('allSettings', 'paymentSettings', 'systemSettings', 'notificationSettings'));
    }

    /**
     * Update a system setting
     */
    public function update(Request $request, string $key)
    {
        $request->validate([
            'value' => 'required|string|max:255'
        ]);

        $value = $request->input('value');
        
        if (!$this->systemSettingService->setValue($key, $value)) {
            $this->flasher->error(__('messages.validation.fail.updated'));
            return back()->withErrors([$key => $this->systemSettingService->getValidationMessage($key)]);
        }
        $this->flasher->crudSuccess('updated');
        return redirect()->back();
    }

    /**
     * Refresh system settings cache
     */
    public function refreshCache()
    {
        $this->systemSettingService->refreshCache();
        
        return back()->with('success', 'Settings cache refreshed successfully');
    }
} 