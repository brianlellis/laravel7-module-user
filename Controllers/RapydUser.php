<?php


namespace Rapyd;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Rapyd\Model\UserRoles;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Support\Facades\Auth;

class RapydUser extends Controller
{
  protected static $validators = [
    'email'             => 'required|email|unique:\App\User,email',
    'name_first'        => 'required',
    'name_last'         => 'required'
  ];

  public static function address_by_ip()
  { 
    $url  = 'http://ip-api.com/php/'.request()->ip();
    $ch   = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    $data = curl_exec($ch);
    curl_close($ch);

    return unserialize($data);
  }

  // Display a listing of the resource.
  public static function get_users($order_by = false, $order_sort = false, $role = false)
  {
    if($role) {
      $data = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->select('users.*', 'model_has_roles.role_id')
                ->where('role_id', $role)
                ->orderBy($order_by, $order_sort)
                ->paginate(25);

    } elseif ($order_by) {
      $data = User::orderBy($order_by, $order_sort)->paginate(25);
    } else {
      $data = User::orderBy('id')->paginate(25);
    }

    return $data;
  }

  // Store a newly created resource in storage.
  public function store(Request $request)
  {
    $validator = $this->validate($request, self::$validators);
    $input = $request->except(['role_name', 'password_confirm', '_token']);

    if (isset($input['phone_main'])) {
      $input['phone_main'] = intval(preg_replace('/[^0-9]/', '', $input['phone_main']));
    }

    $input['password']          = Hash::make($input['password']);
    $input['email_verified_at'] = Carbon::now();
    $input['is_approved']       = 1;

    $user = User::create($input);

    $this->requestResetPassword($request);
    $user->syncRoles($request->role_name);
    $user->get_coordinates();

    // System events passed use the model id
    // The model id is used to locate the starting
    // point of the model relation modeler in \RapydEventEmailModel::()
    \RapydEvents::send_mail(
      'user_created', 
      ['event_group_model_id'=>$user->id]
    );

    return redirect(request()->getSchemeAndHttpHost().'/admin/user/dashboard')->with('success', 'User created successfully');
  }

  // Display the specified resource.
  public static function show($user_id)
  {
    return User::find($user_id);
  }


  // Show the form for editing the specified resource.
  public function edit($id)
  {
    $user = User::find($id);
    $roles = Role::pluck('name', 'name')->all();
    $userRole = $user->roles->pluck('name', 'name')->all();

    return view('users.edit', compact('user', 'roles', 'userRole'));
  }


  // Update the specified resource in storage.
  public function update(Request $request)
  {
    $input = $request->all();
    
    // GREP FIX - COME BACK AND FIX THIS AS AVATARS HAVE BEEN MOVED TO S3 BUCKET
    // User Avatar
    // if ($request->avatar) {
    //   $image = $request->file('avatar');
    //   $image->move(public_path('user/avatar'), $image->getClientOriginalName());
    //   $input['avatar'] = 'user/avatar/' . $image->getClientOriginalName();
    // }

    if (isset($input['phone_main'])) {
      $input['phone_main'] = intval(preg_replace('/[^0-9]/', '', $input['phone_main']));
    }

    $user = User::find($request->id);

    // If User is currently unapproved then send approval email
    if ($user->is_approved === 0 && (int)$request->is_approved === 1) {
      $this->requestResetPassword($request);
    }

    // User Role
    if (
      (!count($user->getRoleNames()) || $user->getRoleNames()[0] != 'Suspended User') &&
      $input['user_role'] == 'Suspended User'
    ) {
      \RapydEvents::send_mail('user_suspended', ['passed_user'=>$user]);
    }
    $user->syncRoles($input['user_role']);

    unset($input['user_role']);

    $user->update($input);
    $user->get_coordinates();
    \RapydEvents::send_mail('user_updated', ['passed_user'=>$user]);
    \FullText::reindex_record('\\App\\User', $request->id);

    return back()->with('success', 'User updated successfully');
  }

  public function update_password(Request $request)
  {
    
    $this->validate($request, [
      'password'   => 'required|same:password_confirm',
    ]);

    $input = $request->except('password_confirm');
    $input['password'] = Hash::make($input['password']);
    $user = User::find($request->id);
    $user->update($input);

    return back()->with('success', 'Password updated successfully');
  }

  // Request Password Reset
  public function requestResetPassword(REQUEST $request)
  {

    $user = User::where('email', $request->email)->orWhere('id', $request->id)->first();

    if ($user) {
      $hashed_password = Hash::make($request->email);
      $user->update([
        'password_reset'       => $hashed_password,
        'password_reset_force' => 0
      ]);
      \RapydEvents::send_mail('user_password_request', ['passed_user'=>$user]);
    }

    return redirect(request()->getSchemeAndHttpHost().'/login')->with(['password_reset' => 'requested']);
  }

  // Verify User Reset Token And Update Password
  public static function setResetPassword(REQUEST $request)
  {
    $email = $request->email;
    $user = User::where('email', $email)->first();

    if ($user && $user->password_reset === $request->reset_key) {
      $hashed_password = Hash::make($request->password);

      $user->update([
        'password'               => $hashed_password,
        'password_reset'         => '',
        'password_reset_force'   => 0
      ]);

      $user->markEmailAsVerified();
      Auth::login($user);
    }

    return redirect(request()->getSchemeAndHttpHost().'/admin/dashboard');
  }

  public function destroy($user_id)
  {
    $user = User::find($user_id);
    \RapydEvents::send_mail('user_removed', ['passed_user'=>$user]);
    $user->delete();
    return redirect(request()->getSchemeAndHttpHost().'/admin/user/dashboard')->with('success', 'User removed successfully');
  }

  public function avatar(Request $request)
  {
    //  Avatar
    if ($request->avatar) {
      $image = $request->file('avatar');
      // GREP FIX: Change location to an S3 Bucket
      $image->move(public_path('user/avatar'), $image->getClientOriginalName());
      User::find($request->user)->update(['avatar' => 'user/avatar/' . $image->getClientOriginalName()]);
    }

    return back();
  }

  public static function internal_users()
  {
    return \App\User::whereHas("roles", function($q){
      $q->where("name", "Developer")
        ->orWhere("name", "Internal Admin")
        ->orWhere("name", "Internal")
        ->orWhere("name", "Underwriter");
    })->where('name_first','!=','')->where('name_last','!=','')->orderBy('name_first')->get();
  }

  public static function get_avatar($use_id = false)
  {
    $user = self::show(request()->get('user_id')) ?: \Auth::user();
    if($user) {
      if ($user->avatar) {
        return '<img src="'.$avatar_path.'" alt="User Avatar" class="userpic brround">';
      } else {
        $initials = $user->name_first[0].$user->name_last[0];
        return '<div class="userpic brround">'.$initials.'</div>';
      }
    } else {
      $domain_source = \SettingsSite::get('system_policy_domain_source');
      return '<div class="userpic brround">'.($domain_source ?? 'NA').'</div>';
    }
  }
}
