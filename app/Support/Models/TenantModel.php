<?php

namespace App\Support\Models;

use App\Support\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

abstract class TenantModel extends Model
{
    use TenantScoped;

    protected $guarded = ['id'];
}
