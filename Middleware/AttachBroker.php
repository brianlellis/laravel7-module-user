<?php

namespace Rapyd\Sso\Middleware;

use Closure;
use CodeEdu\LaravelSso\Sso\Broker;

class AttachBroker
{
  /**
   * Handle an incoming request.
   *
   * @param \Illuminate\Http\Request $request
   * @param \Closure $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    $broker = new \SsoBroker();
    $redirect = $broker->attach(true);
    if ($redirect) {
      return $redirect;
    }
    return $next($request);
  }
}
