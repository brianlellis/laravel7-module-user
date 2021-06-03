<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class PermissionCategories extends Model
{
    protected $table = 'permission_categories';

    protected $guarded = [];

    public $timestamps = false;
}
