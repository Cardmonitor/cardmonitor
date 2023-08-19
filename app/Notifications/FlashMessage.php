<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class FlashMessage extends Notification
{
    use Queueable;

    private array $message = [];

    public static function success(string $text)
    {
        return new self($text, 'success');
    }

    public static function danger(string $text)
    {
        return new self($text, 'danger');
    }

    public function __construct(string $text, string $type = 'success')
    {
        $this->message = [
            'type' => $type,
            'text' => $text,
        ];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['broadcast'];
    }

    public function toBroadcast($notifiable)
{
    return new BroadcastMessage([
        'message' => $this->message
    ]);
}
}
