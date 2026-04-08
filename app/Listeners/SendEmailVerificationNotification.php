<?php

namespace App\Listeners;

use App\Mail\VerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

class SendEmailVerificationNotification
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        Mail::to($event->user->email)->send(new VerifyEmail($event->user));
    }
}
