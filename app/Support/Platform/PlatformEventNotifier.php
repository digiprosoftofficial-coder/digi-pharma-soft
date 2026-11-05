<?php

namespace App\Support\Platform;

use App\Models\User;
use App\Notifications\PlatformEventNotification;
use App\Support\Permission\PlatformTeam;
use App\Support\Notifications\WhatsAppNotifier;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

final class PlatformEventNotifier
{
    public function __construct(private readonly WhatsAppNotifier $whatsApp) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyOperators(string $event, string $title, string $body, array $data = [], ?string $whatsappTo = null): void
    {
        $recipients = $this->platformUsers();

        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new PlatformEventNotification($event, $title, $body, $data),
            );
        }

        if ($whatsappTo) {
            $this->whatsApp->send($whatsappTo, $title.' — '.$body);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function platformUsers()
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);

        try {
            return User::query()
                ->whereNull('tenant_id')
                ->where(function ($q) {
                    $q->where('is_platform_super_admin', true)
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['super admin', 'sales']));
                })
                ->get();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
