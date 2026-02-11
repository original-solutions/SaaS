<?php

namespace App\Mail;

use App\Models\TenantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TenantInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(public TenantInvitation $invitation)
    {
        $this->acceptUrl = URL::signedRoute('invitations.accept', [
            'token' => $this->invitation->token,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to '.$this->invitation->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant-invitation',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
