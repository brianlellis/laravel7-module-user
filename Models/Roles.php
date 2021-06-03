<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
  protected $table    = 'roles';
  protected $guarded  = [];
  public $timestamps  = false;
}
