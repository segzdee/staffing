<?php

namespace App\Mail;

use Illuminate\Notifications\Notifiable;

/**
 * A notifiable class for sending notifications to email addresses
 * that don't have an associated user model.
 */
class NotifiableEmail
{
    use Notifiable;

    protected string $email;
    protected ?string $name;

    /**
     * Create a new notifiable email instance.
     *
     * @param string $email
     * @param string|null $name
     */
    public function __construct(string $email, ?string $name = null)
    {
        $this->email = $email;
        $this->name = $name;
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param \Illuminate\Notifications\Notification|null $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification = null): array|string
    {
        if ($this->name) {
            return [$this->email => $this->name];
        }

        return $this->email;
    }

    /**
     * Get the email address.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
