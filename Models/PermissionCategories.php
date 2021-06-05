<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class PermissionCategories extends Model
{
  protected $connection = 'service_users';
  protected $table      = 'permission_categories';
  protected $guarded    = [];
  public $timestamps    = false;
}
