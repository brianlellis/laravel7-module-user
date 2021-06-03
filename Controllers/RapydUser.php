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
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
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

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    $validator = $this->validate($request, [
      'name_first' => 'required',
      'name_last'  => 'required',
      'email'      => 'required|email|unique:users',
      'password'   => 'required|same:password_confirm',
    ]);


    $input = $request->except(['role_name', 'password_confirm', '_token']);

    if (isset($input['phone_main'])) {
      $input['phone_main'] = intval(preg_replace('/[^0-9]/', '', $input['phone_main']));
    }

    $input['password']          = Hash::make($input['password']);
    $input['email_verified_at'] = Carbon::now();
    $input['is_approved']       = 1;

    $user = User::create($input);

    // Allow User To Create Password
    $this->requestResetPassword($request);

    // User Role
    $user->syncRoles($request->role_name);

    // Get User Address For Google Map
    $user->get_coordinates();

    // Create User Avatar
    $user->create_avatar();

    return redirect('/admin/user/dashboard')->with('success', 'User created successfully');
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public static function show($user_id)
  {
    return User::find($user_id);
  }


  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
    $user = User::find($id);
    $roles = Role::pluck('name', 'name')->all();
    $userRole = $user->roles->pluck('name', 'name')->all();

    return view('users.edit', compact('user', 'roles', 'userRole'));
  }


  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request)
  {
    $validator = $this->validate($request, [
      'id'         => 'required',
      'name_first' => 'required',
      'name_last'  => 'required',
    ]);

    $input = $request->all();

    // User Avatar
    if ($request->avatar) {
      $image = $request->file('avatar');
      $image->move(public_path('user/avatar'), $image->getClientOriginalName());
      $input['avatar'] = 'user/avatar/' . $image->getClientOriginalName();
    }

    if (isset($input['phone_main'])) {
      $input['phone_main'] = intval(preg_replace('/[^0-9]/', '', $input['phone_main']));
    }

    $user = User::find($request->id);

    // If User is currently unapproved then send approval email
    if ($user->is_approved === 0 && (int)$request->is_approved === 1) {
      $this->requestResetPassword($request);
    }

    // User Role
    $user->syncRoles($input['user_role']);

    unset($input['user_role']);

    $user->update($input);
    $user->get_coordinates();

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

      \RapydMail::build_email_template(
        'system-default',
        'user-password-create',
        $user->email,
        $user->full_name(),
        ['event_mail_subject' => $request->subject ?? 'BondExchange Create Password'],
        [
          'hash_key'   => $hashed_password,
          'subject'   => $request->subject ?? 'Account Approved',
          'message'   => $request->message ?? "Congratulations you're account has been approved! "
        ]
      );
    }

    return redirect('/login')->with(['password_reset' => 'requested']);
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

    return redirect('/admin/dashboard');
  }

  public function destroy($user_id)
  {
    User::find($user_id)->delete();
    return redirect('/admin/user/dashboard')->with('success', 'User removed successfully');
  }

  public function avatar(Request $request)
  {
    //  Avatar
    if ($request->avatar) {
      $image = $request->file('avatar');
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
}
