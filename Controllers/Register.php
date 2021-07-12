<?php

namespace Rapyd\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use Rapyd\Model\Usergroups;
use Rapyd\Model\UsergroupType;


class Register
{
  protected static $validators = [
    'email'             => 'required|email|unique:\App\User,email',
    'name_first'        => 'required',
    'name_last'         => 'required',
    'phone_main'        => 'nullable'
  ];

  public function is_blocked_domain($email)
  {
    $blocked_domains = explode(',', \SettingsSite::get('registration_blocked_domains'));
    $email_domain    = explode('@', $email)[1];

    foreach ($blocked_domains as $blocked_domain) {
      if (strpos($email_domain, $blocked_domain) !== false) { return true; }
    }
    return false;
  }

  public function create_user(Request $request)
  {
    $rapyd_event  = \DB::table('rapyd_events')
                        ->where('id','user_registered_blocked')->first();

    $data = $request->validate(self::$validators);

    // PASSWORD CHECK
    if (isset($data['password'])) {
      $data['password']     = Hash::make($data['password']);
    } else {
      $data['password']             = 'nopass';
      $data['password_reset_force'] = 1;
    }

    if($request->phone_main) {
      $data['phone_main'] = preg_replace('/[^0-9]/', '', $data['phone_main']);
    }

    $user = User::create($data);
    $user->get_coordinates();

    // USERGROUP
    if ($request->usergroup_id) {
      \RapydUsergroups::add_user($request->usergroup_id, $user->id);
    }

    $user = \App\User::find(6);
    if (self::is_blocked_domain($request->email)) {
      $user->assignRole($request->role_name ?? 'Unapproved User');
      \RapydEvents::send_mail('user_registered_blocked', [ 'agent' => $user ]);
      $redirect     = '/registration-awaiting-approval';
    } else {
      $user->assignRole($request->role_name ?? 'Normal User');
      \RapydEvents::send_mail('user_registered_success', [ 'agent' => $user ]);
      $redirect     = '/registration-success';
    }
    \RapydEvents::send_mail($rapyd_event, ['agent'=>$user]);

    \FullText::reindex_record('\\App\\User', $user->id);
    // CUSTOM ROUTES COULD BE DUE TO A NEED TO OVERRIDE REDIRECT
    // IF CREATION OF USER IS DONE IN ADMIN
    if($request->custom_route) {
      return redirect($request->custom_route)->with('success','User successfully added');
    }
    return redirect(request()->getSchemeAndHttpHost().$redirect);
  }

  // This Route Is For Ajax Request And Sends Back A JSON Response
  public function ajax_create_user(Request $request)
  {
    $data       = $request->validate(self::$validators);
    $user_group = Usergroups::find($request->usergroup);

    if (!$user_group) {
      $user_group = Usergroups::create([
        'name'              => $request->business_name,
        'address_street'    => $request->address_street,
        'address_street_2'  => $request->address_street_2,
        'address_city'      => $request->address_city,
        'address_state'     => $request->address_state,
        'address_zip'       => intval($request->address_zip),
        'usergroup_type_id' => UsergroupType::where('description', 'agency')->first()->id,
      ]);
    }

    $data['phone_main']     = preg_replace('/[^0-9]/', '', $data['phone_main']);
    $data['password']       = Hash::make($data['email'] . $data['name_first'] . $data['name_last']);
    $data['page_url_slug']  = \RapydCore::slugify("{$request->business_name} {$request->name_first} {$request->name_last}");

    $user = User::create($data);
    $user_group->users()->attach($user->id);
    $user_group->get_coordinates();
    $user->get_coordinates();
    $user->assignRole('Agent');
    \FullText::reindex_record('\\App\\User', $user->id);

    if (self::is_blocked_domain($request->email)) {
      $user->assignRole($request->role_name ?? 'Unapproved User');
      \RapydEvents::send_mail('user_registered_blocked', ['agent' => $user, 'agency' => $user_group]);
      $msg          = 'Email Domain Has Been Blocked';
      $success      = false;
    } else {
      $user->assignRole($request->role_name ?? 'Normal User');
      \RapydEvents::send_mail('user_registered_success', ['agent' => $user, 'agency' => $user_group]);
      $msg          = 'User has successfully registered';
      $success      = true;
    }
    
    return ['success' => $success, 'msg' => $msg];
  }

  public function verify_email($hashdash)
  {
    User::find($hashdash)->markEmailAsVerified();
    return redirect(request()->getSchemeAndHttpHost().'/')->with('success', 'You have succesfully confirmed your email and may now login');
  }
}
