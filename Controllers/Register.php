<?php

namespace Rapyd\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use Rapyd\Model\Usergroups;
use Rapyd\Model\UsergroupType;


class Register
{
  public function is_blocked_domain($email)
  {
    $blocked_domains = explode(',', \SettingsSite::get('registration_blocked_domains'));
    $email_domain    = explode('@', $email)[1];

    foreach ($blocked_domains as $blocked_domain) {
      if (strpos($email_domain, $blocked_domain) !== false) {
        return true;
      }
    }

    return false;
  }

  public function create_user(Request $request)
  {
    $data = $request->validate([
      'email'             => 'required|email|unique:\App\User,email',
      'name_first'        => 'required',
      'name_last'         => 'required',
      'password'          => 'nullable',
      'phone_main'        => 'nullable',
      'address_street'    => 'nullable',
      'address_street_2'  => 'nullable',
      'address_city'      => 'nullable',
      'address_state'     => 'nullable',
      'address_zip'       => 'nullable',
      'title'             => 'nullable',
      'social_website'    => 'nullable',
      'bio'               => 'nullable',
    ]);

    // PASSWORD CHECK
    if (isset($data['password'])) {
      $data['password']     = Hash::make($data['password']);
    } else {
      $data['password']             = 'nopass';
      $data['password_reset_force'] = 1;
    }

    if($request->phone_main) {
      $data['phone_main']   = preg_replace('/[^0-9]/', '', $data['phone_main']);
    }

    $user = User::create($data);
    $user->get_coordinates();

    // USERGROUP
    if ($request->usergroup_id) {
      \RapydUsergroups::add_user($request->usergroup_id, $user->id);
    }

    if (self::is_blocked_domain($request->email)) {
      // ROLENAME
      if ($request->role_name) {
        $user->assignRole($request->role_name);
      } else {
        $user->assignRole('Unapproved User');
      }

      $user['event_mail_subject'] = "User Blocked";
      \RapydEvents::send_mail('user_registered_blocked_system', 'sitewide_notification_emails', $user);

      $user['event_mail_subject'] = "BondExchange Registration Fail";
      \RapydEvents::send_mail('user_registered_blocked', false, $user);

      if($request->custom_route) {
        return redirect($request->custom_route)->with('success','User successfully added');
      } else {
        return redirect(request()->getSchemeAndHttpHost().'/registration-awaiting-approval');
      }
    } else {
      // ROLENAME
      if ($request->role_name) {
        $user->assignRole($request->role_name);
      } else {
        $user->assignRole('Normal User');
      }

      // To Admin
      $user['event_mail_subject'] = "User Enrolled";
      \RapydEvents::send_mail('user_created_system', 'sitewide_notification_emails', $user);

      //  To User
      $user['event_mail_subject'] = "SuretyPedia Verify Email";
      \RapydEvents::send_mail('user_created', false, $user, $data);

      if($request->custom_route) {
        return redirect($request->custom_route)->with('success','User successfully added');
      } else {
        return redirect(request()->getSchemeAndHttpHost().'/registration-success');
      }
    }

    \FullText::reindex_record('\\App\\User', $user->id);
  }

  // This Route Is For Ajax Request And Sends Back A JSON Response
  public function ajax_create_user(Request $request)
  {
    $data = $request->validate(
      [
        'email'             => 'required|email|unique:users',
        'name_first'        => 'required',
        'name_last'         => 'required',
        'password'          => 'nullable',
        'phone_main'        => 'nullable',
        'address_street'    => 'nullable',
        'address_street_2'  => 'nullable',
        'address_city'      => 'nullable',
        'address_state'     => 'nullable',
        'address_zip'       => 'nullable',
        'title'             => 'nullable',
        'social_website'    => 'nullable',
        'bio'               => 'nullable',
      ]
    );

    $user_group = Usergroups::find($request->usergroup);

    if (!$user_group) {
      $user_group = Usergroups::create([
        'name'                    => $request->business_name,
        'address_street'          => $request->address_street,
        'address_street_2'        => $request->address_street_2,
        'address_city'            => $request->address_city,
        'address_state'           => $request->address_state,
        'address_zip'             => intval($request->address_zip),
        'usergroup_type_id'       => UsergroupType::where('description', 'agency')->first()->id,
      ]);
    }

    $data['phone_main']             = preg_replace('/[^0-9]/', '', $data['phone_main']);
    $data['password']               = Hash::make($data['email'] . $data['name_first'] . $data['name_last']);
    $data['page_url_slug']          = \RapydCore::slugify($request->business_name . ' ' .$request->name_first . ' ' . $request->name_last);

    $user = User::create($data);
    $user_group->users()->attach($user->id);
    $user_group->get_coordinates();
    $user->get_coordinates();
    $user->assignRole('Agent');
    \FullText::reindex_record('\\App\\User', $user->id);

    if (self::is_blocked_domain($request->email)) {
      $user['event_mail_subject'] = "User Blocked";
      \RapydEvents::send_mail('user_registered_blocked_system', 'sitewide_notification_emails', $user);

      $user['event_mail_subject'] = "BondExchange Registration Fail";
      \RapydEvents::send_mail('user_registered_blocked', false, $user);

      return ['success' => false, 'msg' => 'Email Domain Has Been Blocked'];
    } else {
      // To Admin
      $user['event_mail_subject'] = "User Enrolled";
      $user['group_name']         = $user_group->name;
      \RapydEvents::send_mail('user_created_system', 'sitewide_notification_emails', $user);

      return ['success' => true, 'msg' => 'User has successfully registered'];
    }
  }

  public function verify_email($hashdash)
  {
    User::find($hashdash)->markEmailAsVerified();
    return redirect(request()->getSchemeAndHttpHost().'/')->with('success', 'You have succesfully confirmed your email and may now login');
  }
}
