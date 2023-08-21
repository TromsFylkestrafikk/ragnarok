<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail message to notify user about deleted account.
 */
class UserAccountDeleted extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @var \App\Models\User
     */
    protected $user;
    protected $admin;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $admin)
    {
        $this->user = $user;
        $this->admin = $admin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown(
            'emails.user.deleted',
            [
                'app_url' => config('app.url'),
                'user' => $this->user,
                'admin' => $this->admin,
            ],
        )->subject(sprintf("[%s] Slettet brukerkonto", config('app.name')));
    }
}
