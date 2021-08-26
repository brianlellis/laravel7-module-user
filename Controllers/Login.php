<?php

namespace Rapyd\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role as SpatieRole;

class Login
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return Response
     */
    public function authenticate(Request $request)
    {
      $credentials = $request->only('email', 'password');

      if (Auth::attempt($credentials)) {
        $user     = \Auth::user();
        $role     = $user->roles->first();
        $redirect = $role->signin_redirect;

        // Authentication passed...
        if(session()->has('url.intended')) {
          return redirect(session()->get('url.intended'));
        }elseif ($redirect) {
          return redirect(request()->getSchemeAndHttpHost().$redirect);
        } else {
          return redirect(request()->getSchemeAndHttpHost().'/admin/dashboard');
        }
      } else {
				return redirect(request()->getSchemeAndHttpHost().'/login')
				->withErrors(
					[
						'email' 		=> 'Make Sure Your Email Is Correct',
						'password' 	=> 'Make Sure Your Password Is Correct',
					]
				)->withInput(['email' => $request->email]);
      }
    }

    public function logout()
    {
      if (\SettingsSite::get('system_use_sso') == 'on') {
        $cur_session_id = \Session::getId();
        \m_SessionShare::where('session_id',$cur_session_id)->delete();
        \Session::forget('session_share_set');
        
        \DB::connection('service_users')->table('sessions')
          ->where('id',$cur_session_id)->delete();
      }

      Auth::logout();
      return redirect(request()->getSchemeAndHttpHost().'/');
    }
}
