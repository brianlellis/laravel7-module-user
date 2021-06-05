<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class UserRoles extends Model
{
  protected $connection = 'service_users';
  protected $table      = 'model_has_roles';
  protected $guarded    = [];
  public $timestamps    = false;
}
