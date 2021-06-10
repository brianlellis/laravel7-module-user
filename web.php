<?php

//--------------- SESSION CHECKER
Route::get('/api/needanewsletter', function() {
  $client_id  = request()->get('visit');
  $session_id = request()->get('campaign');
  $session    = \m_SessionShare::where('client_id',$client_id)->first();

  if (!$session) {
    \Session::put('session_share_start', true);
    \m_SessionShare::insert([
      'client_id'   => $client_id,
      'session_id'  => $session_id,
      'ip'          => request()->ip()
    ]);
  }
});

Route::get('/api/findanewsletter', function() {
  $client_id  = request()->get('visit');
  $session    = \m_SessionShare::where('client_id',$client_id)
                  ->where('ip',request()->ip())->first();

  if ($session) {
    // REMOVE ARTIFACT SESSION ID
    $cur_session_id = \Session::getId();
    \DB::connection('service_users')->table('sessions')
        ->where('id',$cur_session_id)->delete();

    \Session::setId($session->session_id);
    \Session::start();
    \Session::put('session_share_set', true);
    return 'found';
  } else {
    return 'no: '.$client_id;
  }
});


//--------------- LOGIN
Route::get('login', ['as' => 'login', function () {
	if (Auth::user()) {
		return redirect('/admin/dashboard');
	}

	// GET BLADE INFORMATION
	// IMPORTANT WE DO IT HERE TO CONTROL THE HEADER RESPONSE
	// EXAMPLE 404 header response can only be sent server side
	$via_pageslug = $pageslug_data = $view_lookup = false;
	$blade_data   = PublicScaffold::getBladeData();

	// LOAD CONTENT BODY OF PAGE
	$view_header   = 200;

	if (View::exists('rapyd_module_public::' . $blade_data['blade_val'])) {
		$view_lookup = 'rapyd_module_public::' . $blade_data['blade_val'];
	} elseif (View::exists('theme::' . $blade_data['blade_val'])) {
		$view_lookup = 'theme::' . $blade_data['blade_val'];
	} else {
		// Check as a last option if the page is via url_slug in the CMS tables
		// NOTE: 1 - BlogPost, 2 - CMS Page via Laraberg
		$pageslug_data = \Rapyd\Model\CmsBlogPost::where('url_slug', $blade_data['url_path'])->first();
		if (!$pageslug_data) {
			$pageslug_data = \Rapyd\Model\CmsPage::where('url_slug', $blade_data['url_path'])->first();
		}

		if ($pageslug_data && $pageslug_data->is_published) {
			$via_pageslug = true;
		} else {
			// CHECK TO MAKE SURE THERE ISN'T A CMS 404 PAGE CREATED
			$pageslug_data = \Rapyd\Model\CmsPage::where('url_slug', '404')->first();
			if ($pageslug_data) {
				$via_pageslug = true;
			} else {
				if (View::exists('theme::404')) {
					$view_lookup = 'theme::404';
				}
			}

			$view_header = 404;
			$blade_data['page_id'] = '404';
		}
	}

	return response()
		->view('rapyd_master::master', [
			'blade_data'    => $blade_data, // IF $via_pageslug TRUE May NOT WORK. This is compiled assets, and labeling via backend, also allows for looking up of the view blade files programmatically
			'view_lookup'   => $view_lookup, // Used if $via_pageslug is false
			'via_pageslug'  => $via_pageslug,
			'pageslug_data' => $pageslug_data
		], $view_header);
}]);

Route::name('rapyd.user.')->prefix('/login/')->group(function () {
  Route::post('auth',                   '\Rapyd\Auth\Login@authenticate')->name('auth');
  Route::get('logout',                  '\Rapyd\Auth\Login@logout')->name('logout');
  Route::post('register',               '\Rapyd\Auth\Register@create_user')->name('register');
  Route::get('verify-email/{hashdash}', '\Rapyd\Auth\Register@verify_email')->name('email.verify');
  Route::post('resetpassword',          '\Rapyd\RapydUser@requestResetPassword')->name('reset.password');
  Route::post('resetpassword/set',      '\Rapyd\RapydUser@setResetPassword')->name('password.set');
});
Route::post('/api/register', '\Rapyd\Auth\Register@ajax_create_user')->name('rapyd.api.register');

/**
 * ADMIN DASHBOARD EDIT, UPDATE, DELETE FUNCTIONALITY
 **/
Route::name('rapyd.')->prefix('/api/')->middleware(['auth', 'verified'])->group(function () {
  Route::name('user.')->prefix('user/')->group(function () {
  	Route::post('create',           '\Rapyd\RapydUser@store')->name('create');
  	Route::post('update',           '\Rapyd\RapydUser@update')->name('update');
  	Route::post('avatar',           '\Rapyd\RapydUser@avatar')->name('avatar');
  	Route::get('delete/{user_id}',  '\Rapyd\RapydUser@destroy')->name('delete');
    Route::post('update-pass',      '\Rapyd\RapydUser@update_password')->name('update_pass');

    Route::name('permission.')->prefix('permissions/')->group(function () {
      Route::post('create', '\Rapyd\RapydUserPermission@create')->name('create');
      Route::post('update', '\Rapyd\RapydUserPermission@update')->name('update');
      Route::get('delete/{permission_id}', '\Rapyd\RapydUserPermission@delete')->name('delete');
    });

    Route::name('role.')->prefix('role/')->group(function () {
      Route::post('create', '\Rapyd\RapydUserRole@create')->name('create');
      Route::post('update', '\Rapyd\RapydUserRole@update')->name('update');
      Route::get('delete/{role_id}', '\Rapyd\RapydUserRole@destroy');
    });
  });

  Route::name('usergroup.')->prefix('usergroup/')->group(function () {
    Route::post('create',                         '\Rapyd\RapydUsergroups@store')->name('create');
    Route::post('avatar',                         '\Rapyd\RapydUsergroups@avatar')->name('avatar');
    Route::post('complete',                       '\Rapyd\RapydUsergroups@complete')->name('complete');
    Route::post('update/{usergroup}',             '\Rapyd\RapydUsergroups@update')->name('update');
    Route::post('delete/{usergroup}',             '\Rapyd\RapydUsergroups@destroy')->name('delete');
    Route::get('adduser/{group_id}/{user_id}',    '\Rapyd\RapydUsergroups@add_user')->name('adduser');
    Route::get('removeuser/{group_id}/{user_id}', '\Rapyd\RapydUsergroups@remove_user')->name('removeuser');
    Route::post('removeagent',                    '\Rapyd\RapydUsergroups@remove_agent')->name('removeuser');
    Route::get('deactivate/{usergroup_id}',       '\Rapyd\RapydUsergroups@deactivate')->name('deactivate');
    Route::get('activate/{usergroup_id}',         '\Rapyd\RapydUsergroups@activate')->name('activate');
    Route::get('producer/override/{usergroup_id}','\Rapyd\RapydUsergroups@producerOverride')->name('producer.override');
  });
});

Route::post('api/usergroup/find', '\Rapyd\RapydUsergroups@getAgency')->name('find');
