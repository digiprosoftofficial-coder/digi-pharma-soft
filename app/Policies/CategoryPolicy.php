<?php

namespace App\Policies;

use App\Domain\Catalog\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.view') && (int) $user->tenant_id === (int) $category->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('categories.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.manage') && (int) $user->tenant_id === (int) $category->tenant_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.manage') && (int) $user->tenant_id === (int) $category->tenant_id;
    }
}
