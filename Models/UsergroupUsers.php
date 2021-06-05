<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class UsergroupUsers extends Model
{
  protected $connection = 'service_users';
  protected $table      = 'usergroup_users';
  protected $guarded    = [];
}
