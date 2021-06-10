<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
  protected $connection = 'service_users';
  protected $table      = 'sessions';
  protected $guarded    = [];
  public $timestamps    = false;
}
