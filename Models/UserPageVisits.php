<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class UserPageVisits extends Model
{
  protected $connection = 'service_users';
  protected $table      = 'user_page_visits';
  protected $guarded    = [];
  public $timestamps    = false;
}
