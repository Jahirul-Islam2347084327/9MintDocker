<?php

namespace App\Observers;

use App\Models\UserNotification;
use App\Mail\NewNotificationMail;
use Illuminate\Support\Facades\Mail;

class UserNotificationObserver
{
    /**
     * Handle the UserNotification "created" event.
     */
    public function created(UserNotification $notification): void
    {
        // 1. Get the user this notification belongs to
        $user = $notification->user; 

        // 2. SAFETY CHECK: Ensure user exists, has an email, AND has notifications turned ON
        if ($user && !empty($user->email) && $user->receives_email_notifications) {
            
            // 3. Package the data for our email template
            $data = [
                // We fallback to a generic message just in case the notification text is missing
                'message' => $notification->data['message'] ?? $notification->message ?? 'Check your dashboard for a new update!' 
            ];

            // 4. Send the email!
            Mail::to($user->email)->send(new NewNotificationMail($data));
        }
    }

    /**
     * Handle the UserNotification "updated" event.
     */
    public function updated(UserNotification $userNotification): void
    {
        //
    }

    /**
     * Handle the UserNotification "deleted" event.
     */
    public function deleted(UserNotification $userNotification): void
    {
        //
    }

    /**
     * Handle the UserNotification "restored" event.
     */
    public function restored(UserNotification $userNotification): void
    {
        //
    }

    /**
     * Handle the UserNotification "force deleted" event.
     */
    public function forceDeleted(UserNotification $userNotification): void
    {
        //
    }
}