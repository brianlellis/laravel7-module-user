<?php


namespace Rapyd\Sso;

use Rapyd\Sso\SsoServer;
use Jasny\ValidationResult;
use App\Models\User;

class SsoBrokerServer extends SsoServer
{
  // Consider changing to DB value
  private $brokers = [
    '1' => 'secret1',
    '2' => 'secret2'
  ];

  // Authenticate using user credentials
  protected function authenticate($username, $password)
  {
    if (!\Auth::guard('web')->validate(['email' => $username, 'password' => $password])) {
      return ValidationResult::error(trans('auth.failed'));
    }

    return ValidationResult::success();
  }

  // Get the secret key and other info of a broker
  protected function getBrokerInfo($brokerId)
  {
    return !array_key_exists($brokerId, $this->brokers) ? null : [
      'id' => $brokerId,
      'secret' => $this->brokers[$brokerId]
    ];
  }

  // Get the information about a user
  protected function getUserInfo($username)
  {
    $user = User::whereEmail($username)->first();
    return !$user ? null : [
      'user' => $user,
    ];
  }
}