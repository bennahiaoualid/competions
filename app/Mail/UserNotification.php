<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class UserNotification extends Mailable
{
    use Queueable, SerializesModels;
    public array $data ;
    public string $locale_;

    /**
     * Create a new message instance.
     */
    public function __construct($data,$locale_ = 'en')
    {
        $this->data = $data;
        $this->locale_ = $locale_;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Extract domain from APP_URL environment variable (Laravel standard)
        $appUrl = env('APP_URL', 'http://localhost');
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $fromEmail = 'notify@' . $domain;

        return new Envelope(
            from: new Address("support@knowledg-community.space", 'administrator'),
            subject: $this->data['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        App::setLocale($this->locale_);
        return new Content(
            view: 'mails.user_notify_mail_competition_peoccess',
            with: [
                'data' => $this->data,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
