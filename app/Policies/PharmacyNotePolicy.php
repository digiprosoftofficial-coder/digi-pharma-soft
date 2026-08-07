<?php

namespace App\Policies;

use App\Domain\Tenant\Models\PharmacyNote;
use App\Models\User;

class PharmacyNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notes.view');
    }

    public function view(User $user, PharmacyNote $pharmacyNote): bool
    {
        return $user->can('notes.view') && (int) $user->tenant_id === (int) $pharmacyNote->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('notes.manage');
    }

    public function update(User $user, PharmacyNote $pharmacyNote): bool
    {
        return $user->can('notes.manage') && (int) $user->tenant_id === (int) $pharmacyNote->tenant_id;
    }

    public function delete(User $user, PharmacyNote $pharmacyNote): bool
    {
        return $user->can('notes.manage') && (int) $user->tenant_id === (int) $pharmacyNote->tenant_id;
    }
}
