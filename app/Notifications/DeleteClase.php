<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeleteClase extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $fecha, public $atleta)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $name = explode(' ', $this->atleta->name)[0];
        $fecha = $this->fecha->tz('Europe/Madrid')->format('d/m/Y \a \l\a\s G:i');

        return (new MailMessage)
                    ->subject("Cross Performance | Clase cancelada")
                    ->greeting("¡Hola, $name!")
                    ->line("Has recibido este mensaje porque su clase del $fecha ha sido cancelada.")
                    ->line('Sentimos las molestias, le invitamos a reservar su próxima clase en nuestro amplio horario.')
                    ->line('¡Gracias por confiar en nosotros!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
