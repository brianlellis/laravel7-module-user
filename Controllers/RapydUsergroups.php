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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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

        return redirect("/admin/usergroups/profile?group={$group_id}")->with('success', 'User added to group');
    }

    public static function remove_user($group_id, $user_id)
    {
        \DB::table('usergroup_users')->where('usergroup_id',$group_id)->where('user_id',$user_id)->delete();

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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $usergroup  = Usergroups::create($this->make_group($request));
        $usergroup->get_coordinates();

        return redirect('/admin/usergroups/dashboard')->with('success','Usergroup created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public static function show($user_id)
    {
        return Usergroups::find($user_id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Usergroups $usergroup)
    {
        $usergroup->update($this->make_group($request));
        $usergroup->get_coordinates();

        \FullText::reindex_record('\\Rapyd\\Model\\Usergroups', $usergroup->id);

        return redirect('/admin/usergroups/dashboard')->with('success','Usergroup updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Usergroups $usergroup)
    {
        $usergroup->delete();
        return redirect('/admin/usergroups/dashboard')->with('success','Usergroup deleted successfully');
    }

    public function make_group($request)
    {
      $input = $request->all();

      // Usergroup Avatar
      if ($request->file('avatar')) {
        $image = $request->file('avatar');
        $image->move(public_path('usergroup/avatar'), $image->getClientOriginalName());
        $input['avatar'] = 'usergroup/avatar/' . $image->getClientOriginalName();
      }

      $input['phone_main']   = preg_replace('/[^0-9]/', '', $input['phone_main']);

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
      $w9pdf          = new Pdf(asset('storage/USERGROUPS/TEMPLATES/w-9.pdf'));

      $w9data = [
        // Persons Name
        'topmostSubform[0].Page1[0].f1_1[0]' => auth()->user()->full_name(),
        // Agency Name
        'topmostSubform[0].Page1[0].f1_2[0]' => $usergroup->name,
        // Agency Address
        'topmostSubform[0].Page1[0].Address[0].f1_7[0]' => $usergroup->address_street,
        'topmostSubform[0].Page1[0].Address[0].f1_8[0]' => $usergroup->address_city . ', ' .$usergroup->address_state . ', ' . $usergroup->address_zip,
        // Entity Type
        'topmostSubform[0].Page1[0].FederalClassification[0].c1_1[0]' => $request->entity_type === "Sole Proprietor" ? 1 : null,
        'topmostSubform[0].Page1[0].FederalClassification[0].c1_1[1]' => $request->entity_type === "LLC - C Corporation" ? 2 : null,
        'topmostSubform[0].Page1[0].FederalClassification[0].c1_1[2]' => $request->entity_type === "LLC - S Corporation" ? 3 : null,
        'topmostSubform[0].Page1[0].FederalClassification[0].c1_1[3]' => $request->entity_type === "LLC - Partnership" ? 4 : null,
      ];

      // SSN Format
      if($request->tax_id_type === 'SSN') {
        $w9data['topmostSubform[0].Page1[0].SSN[0].f1_11[0]'] = \Str::substr($request->tax_id, 0, 3);
        $w9data['topmostSubform[0].Page1[0].SSN[0].f1_12[0]'] = \Str::substr($request->tax_id, 3, 2);
        $w9data['topmostSubform[0].Page1[0].SSN[0].f1_13[0]'] = \Str::substr($request->tax_id, 5, 4);
      }

      // EIN Format
      if($request->tax_id_type === 'EIN') {
        $w9data['topmostSubform[0].Page1[0].EmployerID[0].f1_14[0]'] = \Str::substr($request->tax_id, 0, 2);
        $w9data['topmostSubform[0].Page1[0].EmployerID[0].f1_15[0]'] = \Str::substr($request->tax_id, 2, 7);
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

    public function avatar(Request $request)
    {
      //  Avatar
      if ($request->avatar) {
        $image = $request->file('avatar');
        $image->move(public_path('usergroup/avatar'), $image->getClientOriginalName());
        Usergroups::find($request->usergroup)->update(['avatar' => 'usergroup/avatar/' . $image->getClientOriginalName()]);
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

          \RapydMail::build_email_template(
            'system-default',
            'user-password-create',
            $user->email,
            $user->full_name(),
            ['event_mail_subject' => 'BondExchange Create Password'],
            [
              'hash_key'   => $hashed_password,
              'subject'   => 'Account Approved',
              'message'   => "Congratulations you're account has been approved!"
            ]
          );

          // Error 550 Too Many Emails Per Second
          sleep(1);
        }

        $user->update(['is_approved' => 1]);
      }

      return back()->with('success', 'Usergroup is Activated');
    }

    public static function producerOverride(Request $request, $usergroup_id)
    {
        $usergroup = Usergroups::find($usergroup_id);

        $data['producer_agreement']     = Carbon::now();
        $data['producer_agreement_ip']  = $request->ip();

        $usergroup->update($data);

        return back()->with(['success' => 'Overrode Producer Agreement']);
    }
}
