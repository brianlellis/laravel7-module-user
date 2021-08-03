<?php

namespace Rapyd\Observers;

use App\User;

class UserObserver
{
  public static function model_used()
  { 
    return '\App\User';
  }

  public function created(User $user)
  {
    // \DB::connection('service_users')->table('test')
    //   ->insert(['message' => 'created record_id: '.$user->id]); 
  }

  public function updated(User $user)
  {
    // \DB::connection('service_users')->table('test')
    //   ->insert(['message' => 'updated record_id: '.$user->id]);
  }

  public function deleted(User $user)
  {
    // \DB::connection('service_users')->table('test')
    //   ->insert(['message' => 'deleted record_id: '.$user->id]);
  }

  public function forceDeleted(User $user)
  {
    // \DB::connection('service_users')->table('test')
    //   ->insert(['message' => 'force_delete record_id: '.$user->id]);
  }
}