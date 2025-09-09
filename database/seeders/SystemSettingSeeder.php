<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Payment Rules
            [
                'setting_key' => 'max_daily_transactions',
                'setting_value' => config('settings.defaults.max_daily_transactions'),
                'setting_trans_key' => 'settings.payment.max_daily_transactions'
            ],
            [
                'setting_key' => 'min_competition_coins',
                'setting_value' => config('settings.defaults.min_competition_coins'),
                'setting_trans_key' => 'settings.payment.min_competition_coins'
            ],
            [
                'setting_key' => 'competition_gift',
                'setting_value' => config('settings.defaults.competition_gift'),
                'setting_trans_key' => 'settings.payment.competition_gift'
            ],
            
            [
                'setting_key' => 'second_place_winner_percentage',
                'setting_value' => config('settings.defaults.second_place_winner_percentage'),
                'setting_trans_key' => 'settings.payment.second_place_winner_percentage'
            ],
            [
                'setting_key' => 'third_place_winner_percentage',
                'setting_value' => config('settings.defaults.third_place_winner_percentage'),
                'setting_trans_key' => 'settings.payment.third_place_winner_percentage'
            ],
            
            // System Configuration
            [
                'setting_key' => 'maintenance_mode',
                'setting_value' => 'false',
                'setting_trans_key' => 'settings.system.maintenance_mode'
            ],
            [
                'setting_key' => 'max_file_upload_size',
                'setting_value' => '10485760', // 10MB in bytes
                'setting_trans_key' => 'settings.system.max_file_upload_size'
            ],
            [
                'setting_key' => 'session_timeout',
                'setting_value' => '120', // 2 hours in minutes
                'setting_trans_key' => 'settings.system.session_timeout'
            ],
            
            // Notification Settings
            [
                'setting_key' => 'email_notifications_enabled',
                'setting_value' => 'true',
                'setting_trans_key' => 'settings.notifications.email_enabled'
            ],
            [
                'setting_key' => 'push_notifications_enabled',
                'setting_value' => 'true',
                'setting_trans_key' => 'settings.notifications.push_enabled'
            ],
            
            // AI Question Generation Settings
            [
                'setting_key' => 'global_question_generating_cost',
                'setting_value' => config('settings.defaults.global_question_generating_cost'),
                'setting_trans_key' => 'settings.ai.service_global_question.generating_cost'
            ],
            [
                'setting_key' => 'global_question_custom_difficulty_cost',
                'setting_value' => config('settings.defaults.global_question_custom_difficulty_cost'),
                'setting_trans_key' => 'settings.ai.service_global_question.custom_difficulty_cost'
            ],
            [
                'setting_key' => 'global_question_custom_subject_cost',
                'setting_value' => config('settings.defaults.global_question_custom_subject_cost'),
                'setting_trans_key' => 'settings.ai.service_global_question.custom_subject_cost'
            ],
            [
                'setting_key' => 'global_question_max_output_tokens',
                'setting_value' => config('settings.defaults.global_question_max_output_tokens'),
                'setting_trans_key' => 'settings.ai.service_global_question.max_output_tokens'
            ],
            [
                'setting_key' => 'global_question_premium_cost_percentage',
                'setting_value' => config('settings.defaults.global_question_premium_cost_percentage'),
                'setting_trans_key' => 'settings.ai.service_global_question.premium_cost_percentage'
            ],
            [
                'setting_key' => 'global_question_llm_provider',
                'setting_value' => config('settings.defaults.global_question_llm_provider'),
                'setting_trans_key' => 'settings.ai.service_global_question.llm_provider'
            ],
            [
                'setting_key' => 'global_question_model',
                'setting_value' => config('settings.defaults.global_question_model'),
                'setting_trans_key' => 'settings.ai.service_global_question.model'
            ],
            
            // AI Auditing Settings
            [
                'setting_key' => 'ai_auditing_cost_per_response',
                'setting_value' => config('settings.defaults.ai_auditing_cost_per_response'),
                'setting_trans_key' => 'settings.ai.service_ai_auditing.cost_per_response'
            ],
            [
                'setting_key' => 'ai_auditing_max_response_auditing_at_one_batch',
                'setting_value' => config('settings.defaults.ai_auditing_max_response_auditing_at_one_batch'),
                'setting_trans_key' => 'settings.ai.service_ai_auditing.max_response_auditing_at_one_batch'
            ],
            [
                'setting_key' => 'ai_auditing_llm_provider',
                'setting_value' => config('settings.defaults.ai_auditing_llm_provider'),
                'setting_trans_key' => 'settings.ai.service_ai_auditing.llm_provider'
            ],
            [
                'setting_key' => 'ai_auditing_model',
                'setting_value' => config('settings.defaults.ai_auditing_model'),
                'setting_trans_key' => 'settings.ai.service_ai_auditing.model'
            ]
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                [
                    'setting_value' => $setting['setting_value'],
                    'setting_trans_key' => $setting['setting_trans_key']
                ]
            );
        }

        $this->command->info('System settings seeded successfully!');
    }
} 