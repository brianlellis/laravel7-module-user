<?php


namespace Rapyd;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Rapyd\Model\Usergroups;
use App\User;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use \mikehaertl\pdftk\Pdf;


class RapydUsergroups extends Controller
{
  public static function get_groups($order_by = false, $order_sort = false, $type = false)
  {
    if($type) {
      $data = Usergroups::where('usergroup_type_id', $type)
                ->orderBy($order_by, $order_sort)
                ->paginate(25);
    } elseif ($order_by == 'type') {
      $data = Usergroups::with(['type' => function ($q) use ($order_sort) {
                $q->orderBy('description', $order_sort);
              }])->paginate(25);
    } elseif ($order_by) {
      $data = Usergroups::orderBy($order_by, $order_sort)->paginate(25);
    } else {
      $data = Usergroups::orderBy('id')->paginate(25);
    }

    return $data;
  }

  public static function add_user($group_id, $user_id)
  {
    $group = Usergroups::find($group_id);

    if(!$group->users()->find($user_id)){
      $group->users()->attach($user_id);
    }
    \RapydEvents::send_mail(
      'user_group_added_to', 
      ['user'=>\App\User::find($user_id), 'usergroup' => $group]
    );

    return redirect("/admin/usergroups/profile?group={$group_id}")->with('success', 'User added to group');
  }

  public static function remove_user($group_id, $user_id)
  { 
    Usergroups::where('usergroup_id',$group_id)->where('user_id',$user_id)->delete();
    \RapydEvents::send_mail(
      'user_group_removed_from', 
      ['user'=>\App\User::find($user_id)]
    );

    return back()->with('success', 'User removed from group');
  }

  public static function remove_agent(Request $request)
  {
    $agent      = User::find($request->user);
    $new_agent  = User::find($request->transfer_agent);
    $usergroup  = Usergroups::find($request->usergroup);

    $policies = $agent->created_policies();
    $policies->update(['agent_id' => $new_agent->id]);

    $agent->update(['is_approved' => false]);
    $usergroup->users()->detach($agent);

    return back()->with('success', 'User removed from group');
  }

  public function store(Request $request)
  {
    $usergroup  = Usergroups::create($this->make_group($request));
    $usergroup->get_coordinates();

    \RapydEvents::send_mail(
      'user_group_created', 
      ['usergroup'=> $usergroup]
    );

    return redirect(request()->getSchemeAndHttpHost().'/admin/usergroups/dashboard')->with('success','Usergroup created successfully');
  }

  public static function show($user_id)
  {
      return Usergroups::find($user_id);
  }

  public function update(Request $request, Usergroups $usergroup)
  {
    $usergroup->update($this->make_group($request));
    $usergroup->get_coordinates();

    \FullText::reindex_record('\\Rapyd\\Model\\Usergroups', $usergroup->id);

    \RapydEvents::send_mail(
      'user_group_updated', 
      ['usergroup'=> $usergroup]
    );

    return redirect(request()->getSchemeAndHttpHost().'/admin/usergroups/dashboard')->with('success','Usergroup updated successfully');
  }

  public function destroy(Usergroups $usergroup)
  {
    \RapydEvents::send_mail(
      'user_group_removed', 
      ['usergroup'=> $usergroup]
    );
    $usergroup->delete();
    return redirect(request()->getSchemeAndHttpHost().'/admin/usergroups/dashboard')->with('success','Usergroup deleted successfully');
  }

  public function make_group($request)
  {
    $input                = $request->all();
    $input['phone_main']  = preg_replace('/[^0-9]/', '', $input['phone_main']);
    return $input;
  }

  public function complete(Request $request)
  {
    $usergroup = Usergroups::find($request->id);

    $data = $request->except(['id']);

    $data['producer_agreement']     = Carbon::now();
    $data['producer_agreement_ip']  = $request->ip();

    $usergroup->update($data);

    return ['success' => true, 'redirect' => '/admin/user/profile'];
  }

  public function generate_w9(Request $request, $usergroup)
  {
    $w9pdf  = new Pdf(asset('storage/USERGROUPS/TEMPLATES/w-9.pdf'));
    $field  = 'topmostSubform[0].Page1[0]';
    $fed    = '.FederalClassification[0]';
    $entity = $request->entity_type;
    $w9data = [
      // Persons Name
      "{$field}.f1_1[0]" => auth()->user()->full_name(),
      // Agency Name
      "{$field}.f1_2[0]" => $usergroup->name,
      // Agency Address
      "{$field}.Address[0].f1_7[0]" => $usergroup->address_street,
      "{$field}.Address[0].f1_8[0]" => $usergroup->address(),
      // Entity Type
      "{$field}{$fed}.c1_1[0]" => $entity === "Sole Proprietor" ? 1 : null,
      "{$field}{$fed}.c1_1[1]" => $entity === "LLC - C Corporation" ? 2 : null,
      "{$field}{$fed}.c1_1[2]" => $entity === "LLC - S Corporation" ? 3 : null,
      "{$field}{$fed}.c1_1[3]" => $entity === "LLC - Partnership" ? 4 : null,
    ];

    // SSN Format
    if($request->tax_id_type === 'SSN') {
      $w9data["{$field}.SSN[0].f1_11[0]"] = \Str::substr($request->tax_id, 0, 3);
      $w9data["{$field}.SSN[0].f1_12[0]"] = \Str::substr($request->tax_id, 3, 2);
      $w9data["{$field}.SSN[0].f1_13[0]"] = \Str::substr($request->tax_id, 5, 4);
    }

    // EIN Format
    if($request->tax_id_type === 'EIN') {
      $w9data["{$field}.EmployerID[0].f1_14[0]"] = \Str::substr($request->tax_id, 0, 2);
      $w9data["{$field}.EmployerID[0].f1_15[0]"] = \Str::substr($request->tax_id, 2, 7);
    }

    $w9pdf->fillForm($w9data);
    $w9pdf->needAppearances();
    $w9fileName = 'usergroup_'.$usergroup->id.'_w9.pdf';
    $w9pdf->saveAs('storage/USERGROUPS/W9/'.$w9fileName);

    return 'storage/USERGROUPS/W9/'.$w9fileName;
  }

  public function generate_producer_agreement(Request $request, $usergroup)
  {
    $producerPdf    = new Pdf(asset('storage/USERGROUPS/TEMPLATES/ProducerAgreement.pdf'));

    $producerData = [
      'AgencyName'        => $usergroup->name,
      'AgentSignature'    => auth()->user()->full_name() . ' ' . $request->ip(),
      'IPAddress'         => $request->ip(),
      'AgencyAddress'     => $usergroup->address_street,
      'AgencyPostalCode'  => $usergroup->address_zip,
      'AgencyCity'        => $usergroup->address_city,
      'AgencyState'       => $usergroup->address_state,
      'Date'              => today()->format('m-d-Y'),
    ];

    $producerPdf->fillForm($producerData);
    $producerPdf->needAppearances();
    $producerFileName = 'usergroup_'.$usergroup->id.'_producer.pdf';
    $producerPdf->saveAs('storage/USERGROUPS/PRODUCER/'.$producerFileName);

    return 'storage/USERGROUPS/PRODUCER/'.$producerFileName;
  }

  public static function get_avatar($group_id = false)
  {
    if(!$group_id && request()->get('group')) {
      $group_id = request()->get('group');
    }

    $user = self::show($group_id) ?: \Auth::user()->usergroup();
    if($user) {
      if ($user->avatar) {
        $avatar_url = \Storage::disk('s3')->url($user->avatar);
        return '<img src="'.$avatar_url.'" alt="User Avatar" class="userpic brround">';
      } else {
        $arr_check = explode(' ', $user->name);
        if (count($arr_check) > 1) {
          $initials = $arr_check[0][0].$arr_check[1][0];
        } else {
          $initials = $arr_check[0].$arr_check[1];
        }
        return '<div class="userpic brround">'.$initials.'</div>';
      }
    } else {
      $domain_source = \SettingsSite::get('system_policy_domain_source');
      return '<div class="userpic brround">'.($domain_source ?? 'NA').'</div>';
    }
  }

  public function avatar(Request $request)
  {
    //  Avatar
    if ($request->avatar) {
      $image        = $request->file('avatar');
      $image_name   = preg_replace('/\s+/', '', $image->getClientOriginalName());
      $store        = \Storage::disk('s3')->put('user/avatar/' . $image_name, $image);
      Usergroups::find($request->usergroup)->update(['avatar' => $store]);
    }

    return back();
  }

  public function avatar_remove(Request $request)
  {
    $usergroup = Usergroups::find($request->usergroup);

    if($usergroup->avatar) {
      \Storage::disk('s3')->delete($usergroup->avatar);
      $usergroup->update(['avatar' => null]);
    }

    return back();
  }

  public function getAgency(Request $request)
  {
    $usergroup = Usergroups::where('name', 'LIKE', '%' . $request->business_name . '%' )
      ->where('address_zip', $request->address_zip)
      ->first();

    if($usergroup) {
      return ['success' => true, 'agency' => $usergroup];
    }

    return ['success' => false, 'agency' => null];
  }

  public static function deactivate($usergroup_id)
  {
    $usergroup = Usergroups::find($usergroup_id);
    $usergroup->update(['is_active' => 0]);

    foreach ($usergroup->users as $user) {
      $user->update(['is_approved' => 0]);
    }

    \RapydEvents::send_mail(
      'user_group_deactivated', 
      ['usergroup'=> $usergroup]
    );

    return back()->with('success', 'Usergroup is Deactivated');
  }
  public static function activate($usergroup_id)
  {
    $usergroup = Usergroups::find($usergroup_id);
    $usergroup->update(['is_active' => 1]);

    foreach ($usergroup->users as $user) {

      if ($user && $user->is_approved === 0) {
        $hashed_password = Hash::make($user->email);
        $user->update([
          'password_reset'       => $hashed_password,
          'password_reset_force' => 0
        ]);

        \RapydEvents::send_mail('user_approved', ['user'=> $user]);
        sleep(1); // Error 550 Too Many Emails Per Second
      }

      $user->update(['is_approved' => 1]);
    }

    \RapydEvents::send_mail(
      'user_group_activated', 
      ['usergroup'=> $usergroup]
    );

    return back()->with('success', 'Usergroup is Activated');
  }

  public static function producerOverride(Request $request, $usergroup_id)
  {
    $usergroup                      = Usergroups::find($usergroup_id);
    $data['producer_agreement']     = Carbon::now();
    $data['producer_agreement_ip']  = $request->ip();
    $usergroup->update($data);

    return back()->with(['success' => 'Overrode Producer Agreement']);
  }

  public static function producerSend($usergroup_id)
  {
    $usergroup = Usergroups::findOrFail($usergroup_id);
    
    if(!$usergroup->email) {
      return redirect(request()->getSchemeAndHttpHost() . "/admin/usergroups/profile?group={$usergroup_id}")->with('error', 'Agency Primary Email Required');
    }

    \RapydEvents::send_mail('complete_producer_agreement', [
      'event_group_model_id'  => $usergroup->id,
      'passed_email'          => $usergroup->email
    ]);
  
    return redirect(request()->getSchemeAndHttpHost() . "/admin/usergroups/profile?group={$usergroup_id}")->with('success', 'Producer Agreement Sent');
  }
}
