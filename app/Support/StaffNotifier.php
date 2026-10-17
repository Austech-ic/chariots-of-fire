<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/** Notifies active staff holding a permission (plus the optional shared inbox). */
class StaffNotifier
{
    public static function notify(string $permission, Notification $notification, ?callable $filter = null): void
    {
        $recipients = User::query()->where('is_staff', true)->where('status', User::STATUS_ACTIVE)->whereNotNull('email_verified_at')
            ->with('roles.permissions')->get()
            ->filter(fn (User $u) => $u->hasPermission($permission))
            ->when($filter, fn ($c) => $c->filter($filter));

        NotificationFacade::send($recipients, $notification);

        if ($inbox = config('fco.staff_notification_email')) {
            NotificationFacade::route('mail', $inbox)->notify($notification);
        }
    }
}
