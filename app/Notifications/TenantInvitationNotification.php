<?php

namespace App\Notifications;

use App\Models\TenantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TenantInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public TenantInvitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = URL::signedRoute('invitations.accept', [
            'token' => $this->invitation->token,
        ]);

        return (new MailMessage)
            ->subject('You\'ve been invited to '.$this->invitation->tenant->name)
            ->line('You have been invited to join **'.$this->invitation->tenant->name.'** as a **'.$this->invitation->role->value.'**.')
            ->action('Accept Invitation', $acceptUrl)
            ->line('This invitation expires on '.$this->invitation->expires_at->toFormattedDateString().'.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
