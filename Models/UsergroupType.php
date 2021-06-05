<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class UsergroupType extends Model
{
  protected $connection = 'service_users';
  protected $table      = 'usergroup_types';
  protected $guarded    = [];
  public $timestamps    = false;
}
