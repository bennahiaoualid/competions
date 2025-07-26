<?php

namespace App\Jobs;

use App\Mail\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $userEmailsWithNames;
    protected array $commonEmailData;
    protected string $locale;

    /**
     * Create a new job instance.
     *
     * @param array $userEmailsWithNames Associative array [email => name]
     * @param array $commonEmailData Data common to all emails (e.g., competition details)
     * @param string $locale
     */
    public function __construct(array $userEmailsWithNames, array $commonEmailData, string $locale = 'en')
    {
        $this->userEmailsWithNames = $userEmailsWithNames;
        $this->commonEmailData = $commonEmailData;
        $this->locale = $locale;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->userEmailsWithNames as $email => $name) {
            $emailData = array_merge($this->commonEmailData, [
                'user' => $name, // Add user-specific name
            ]);

            // The logic to convert 'competition_title' to 'competition' is removed
            // as UserNotifyEmail helper methods now directly pass 'competition'.
            // Similarly, any logic for 'level_name' vs 'level' would be handled before this job.

            Mail::to($email)->queue(new UserNotification($emailData, $this->locale));
        }
    }
} 