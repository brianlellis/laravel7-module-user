<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class SessionShare extends Model
{
  protected $connection = 'service_users';
  protected $table      = 'session_share';
  protected $guarded    = [];
  public $timestamps    = false;
}
