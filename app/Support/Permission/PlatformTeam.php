<?php

namespace App\Support\Permission;

/**
 * Spatie "team" id for platform-wide roles and pivot rows (not a real tenant row).
 * MySQL requires PRIMARY KEY columns to be NOT NULL; pivots cannot use NULL tenant_id.
 */
final class PlatformTeam
{
    public const ID = 0;
}
