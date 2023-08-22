<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class FlashMessage extends Notification
{
    use Queueable;

    private array $message = [];
    private array $data = [];

    public static function success(string $text, array $data = [])
    {
        return new self($text, 'success', $data);
    }

    public static function danger(string $text, array $data = [])
    {
        return new self($text, 'danger', $data);
    }

    public function __construct(string $text, string $type = 'success', array $data = [])
    {
        $this->message = [
            'type' => $type,
            'text' => $text,
        ];
        $this->data = $data;
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
            'message' => $this->message,
            'data' => $this->data,
        ]);
    }
}
