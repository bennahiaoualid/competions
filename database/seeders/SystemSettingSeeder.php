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
                'setting_trans_key' => 'settings.ai.global_question_generating_cost'
            ],
            [
                'setting_key' => 'global_question_custom_difficulty_cost',
                'setting_value' => config('settings.defaults.global_question_custom_difficulty_cost'),
                'setting_trans_key' => 'settings.ai.global_question_custom_difficulty_cost'
            ],
            [
                'setting_key' => 'global_question_custom_subject_cost',
                'setting_value' => config('settings.defaults.global_question_custom_subject_cost'),
                'setting_trans_key' => 'settings.ai.global_question_custom_subject_cost'
            ],
            [
                'setting_key' => 'global_question_max_output_tokens',
                'setting_value' => config('settings.defaults.global_question_max_output_tokens'),
                'setting_trans_key' => 'settings.ai.global_question_max_output_tokens'
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