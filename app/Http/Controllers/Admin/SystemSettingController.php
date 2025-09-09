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
     * Display payment settings
     */
    public function payment(): View
    {
        $allSettings = $this->systemSettingService->getAllSettings();
        $settings = $this->systemSettingService->filterSettingsByCategory($allSettings, 'payment');
        $category = 'payment';
        
        return view('pages.admin.system.settings.index', compact('settings', 'category'));
    }

    /**
     * Display system configuration settings
     */
    public function system(): View
    {
        $allSettings = $this->systemSettingService->getAllSettings();
        $settings = $this->systemSettingService->filterSettingsByCategory($allSettings, 'system');
        $category = 'system';
        
        return view('pages.admin.system.settings.index', compact('settings', 'category'));
    }

    /**
     * Display notification settings
     */
    public function notifications(): View
    {
        $allSettings = $this->systemSettingService->getAllSettings();
        $settings = $this->systemSettingService->filterSettingsByCategory($allSettings, 'notifications');
        $category = 'notifications';
        
        return view('pages.admin.system.settings.index', compact('settings', 'category'));
    }

    /**
     * Display AI question generation settings
     */
    public function aiQuestionGeneration(): View
    {
        $allSettings = $this->systemSettingService->getAllSettings();
        $settings = $this->systemSettingService->filterSettingsByCategory($allSettings, 'ai.service_global_question');
        $category = 'service_global_question';
        
        // Define field metadata for AI question generation settings
        $fieldMetadata = [
            'generating_cost' => ['is_select' => false],
            'llm_provider' => ['is_select' => true],
            'model' => ['is_select' => true],
        ];
        
        // Send options for dynamic UI
        $llmProviders = config('llm.providers_list');
        
        $modelOptions = config('llm.models_list');
        
        
        return view('pages.admin.system.settings.index', compact('settings', 'category', 'fieldMetadata', 'llmProviders', 'modelOptions'));
    }

    /**
     * Display AI auditing settings
     */
    public function aiAuditing(): View
    {
        $allSettings = $this->systemSettingService->getAllSettings();
        $settings = $this->systemSettingService->filterSettingsByCategory($allSettings, 'ai.service_ai_auditing');
        $category = 'service_ai_auditing';

        $llmProviders = config('llm.providers_list');
        
        $modelOptions = config('llm.models_list');
        
        $currentProvider = $this->systemSettingService->getValue('ai_auditing_llm_provider');
        // Define field metadata for AI auditing settings
        $fieldMetadata = [
            'cost_per_response' => ['is_select' => false],
            'max_response_auditing_at_one_batch' => ['is_select' => false],
            'ai_auditing_llm_provider' => ['is_select' => true,'select_list' => $llmProviders],
            'ai_auditing_model' => ['is_select' => true,'select_list' => $modelOptions[$currentProvider]],
        ];
        
        
        
        return view('pages.admin.system.settings.ai_auditing', compact('settings', 'category', 'fieldMetadata', 'llmProviders', 'modelOptions'));
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