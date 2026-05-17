<?php

namespace App\Domain\Tenant\Actions;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantOwnerInvitationNotification;
use Illuminate\Support\Facades\Password;

final class SendTenantOwnerInvitationAction
{
    public function execute(User $owner, Tenant $tenant): void
    {
        $token = Password::broker(config('fortify.passwords'))->createToken($owner);

        $owner->notify(new TenantOwnerInvitationNotification($tenant, $token));
    }
}
