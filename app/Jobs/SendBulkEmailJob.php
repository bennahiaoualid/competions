<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Mail\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $userEmailsWithNames;
    protected array $commonEmailData;
    protected string $locale;
    protected bool $usePersonalization;

    /**
     * Create a new job instance.
     *
     * @param array $userEmailsWithNames Associative array [email => name]
     * @param array $commonEmailData Data common to all emails (e.g., competition details)
     * @param string $locale
     * @param bool $usePersonalization Whether to send personalized emails (true) or BCC (false)
     */
    public function __construct(array $userEmailsWithNames, array $commonEmailData, string $locale = 'en', bool $usePersonalization = false)
    {
        $this->userEmailsWithNames = $userEmailsWithNames;
        $this->commonEmailData = $commonEmailData;
        $this->locale = $locale;
        $this->usePersonalization = $usePersonalization;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Sending email job');
        if ($this->usePersonalization) {
            // Send personalized emails (for admins, individuals, or when personalization is needed)
            $this->sendPersonalizedEmails();
        } else {
            // Send BCC emails (for bulk user notifications - much more efficient)
            $this->sendBccEmails();
        }
    }

    /**
     * Send personalized emails to each recipient (original method)
     */
    protected function sendPersonalizedEmails(): void
    {
        Log::info('Sending personalized emails', [
            'userEmailsWithNames' => $this->userEmailsWithNames,
            'commonEmailData' => $this->commonEmailData,
            'locale' => $this->locale,
            'usePersonalization' => $this->usePersonalization
        ]);
        foreach ($this->userEmailsWithNames as $email => $name) {
            $emailData = array_merge($this->commonEmailData, [
                'user' => $name,
            ]);

            Mail::to($email)->queue(new UserNotification($emailData, $this->locale));
        }
    }

    /**
     * Send emails using BCC - much more efficient for bulk notifications
     */
    protected function sendBccEmails(): void
    {
        Log::info('Sending BCC emails', [
            'userEmailsWithNames' => $this->userEmailsWithNames,
            'commonEmailData' => $this->commonEmailData,
            'locale' => $this->locale,
            'usePersonalization' => $this->usePersonalization
        ]);
        $emails = array_keys($this->userEmailsWithNames);
        
        // Use BCC to send to all recipients at once
        Mail::bcc($emails)->queue(new UserNotification($this->commonEmailData, $this->locale));
    }
} 