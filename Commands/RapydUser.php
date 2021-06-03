<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Hash;
use Illuminate\Console\Command;
use App\User;

class RapydUser extends Command
{
  protected $signature   = 'rapyd:user {--action=} {--name_first=} {--name_last=} {--email=} {--password=} {--role=} {--user_id=}';
  protected $description = 'Commands for User actions';

  public function __construct()
  {
    parent::__construct();
  }

  public function handle()
  {
    if ($this->option('action')) {
      if ($this->option('action') == 'create') {
        self::create();
      } elseif ($this->option('action') == 'find') {
        self::find();
      } elseif ($this->option('action') == 'coordinates') {
        self::user_coordinates();
      }
    }
  }

  protected function find()
  {
    dd(User::find($this->option('user_id'))->toArray());
  }

  protected function create()
  {
    $name_first = $this->option('name_first');
    $name_last  = $this->option('name_last');
    $email      = $this->option('email');
    $password   = $this->option('password');
    $user_role  = $this->option('role');

    if (!$name_first || !$name_last || !$email || !$password || !$user_role) {
      $this->error('Missing required info for user creation');
      return;
    }

    $user                     = new User();
    $user->name_first         = $name_first;
    $user->name_last          = $name_last;
    $user->email              = $email;
    $user->password           = Hash::make($password);
    $user->email_verified_at  = Carbon::now();
    $user->save();

    $user->assignRole($user_role);

    $this->info("{$user_role} {$name_first} {$name_last} ({$email}) created");
  }

  protected function user_coordinates()
  {
    \DB::table('user_coordinates')->truncate();
    $users = User::get();
    foreach ($users as $user) {
      $user->get_coordinates();
    }
  }
}