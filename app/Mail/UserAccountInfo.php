<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail message containing user account information.
 */
class UserAccountInfo extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @var \App\Models\User
     */
    protected $user;
    protected $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown(
            'emails.user.new',
            [
                'app_url' => config('app.url'),
                'user' => $this->user,
                'password' => $this->password
            ],
        )->subject(sprintf("[%s] Ny brukerkonto", config('app.name')));
    }
}
