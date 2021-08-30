@php
use Rapyd\Ecomm\Authnet\AuthnetProfile;
use Rapyd\Ecomm\Authnet\OrderHelper;

$tour_check = \Rapyd\Tours::first_visit();

if (request('user_id')) {
  $user = \RapydUser::show(Request::get('user_id'));
  $own_profile = $user->id === auth()->user()->id;
} else {
  $own_profile = true;
  $user = \RapydUser::show(auth()->user()->id);
}

$all_user_roles = \Spatie\Permission\Models\Role::all();
@endphp

@can('user-update-gamify')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Current Reputation: {{ RapydUserGamify::gamifyGetPoints($user->id) }}</h3>
      <div class="card-options">
        <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i class="fe fe-chevron-down"></i></a>
      </div>
    </div>

    <div class="card-body">
      <a href="@url('/api/users/gamify/addpoint/'){{ $user->id }}/bondedit" class="btn btn-primary"
        style="margin-bottom: 10px">
        Add Bond Edit Points
      </a>
      <a href="@url('/api/users/gamify/undopoint/'){{ $user->id }}/bondedit" class="btn btn-primary"
        style="margin-bottom: 10px">
        Remove Bond Edit Points
      </a>

      <a href="@url('/api/users/gamify/addpoint/'){{ $user->id }}/formupload" class="btn btn-primary"
        style="margin-bottom: 10px">
        Add Form Upload Points
      </a>
      <a href="@url('/api/users/gamify/undopoint/'){{ $user->id }}/formupload" class="btn btn-primary"
        style="margin-bottom: 10px">
        Remove Form Upload Points
      </a>

      <a href="@url('/api/users/gamify/addpoint/'){{ $user->id }}/article" class="btn btn-primary"
        style="margin-bottom: 10px">
        Add Article Submission Points
      </a>
      <a href="@url('/api/users/gamify/undopoint/'){{ $user->id }}/article" class="btn btn-primary"
        style="margin-bottom: 10px">
        Remove Article Submission Points
      </a>
      <a href="@url('/api/core-pdf/zero/pdfdownloads/'){{ $user->id }}" class="btn btn-primary"
        style="margin-bottom: 10px">
        Reset PDF Downloads to Zero
      </a>
    </div>
  </div>
@endcan

@if (Auth::user()->can('user-update') || $own_profile)
  <div class="row">
    <div class="col-lg-3 col-xl-3 col-md-12 col-sm-12" id="col-left">
      <div class="card">
        <div class="card-body">
          <div class="text-center">
            <div class="userprofile">
              {!!\RapydUser::get_avatar($user->id)!!}
              <h3 class="mb-2 username text-dark">{{ $user->name_first }} {{ $user->name_last }}</h3>

              {{-- NOTE: PART OF USER GROUPS TO BE WORKED ON --}}
              {{-- <a class="mb-2 text-dark" href="@url('admin/user/group-profile')">Ellis
        Insurance</a> --}}

              {{-- STAR RATING --}}
              {{-- NOTE: SOMETHING LATER TO BE WORKED ON --}}
              {{-- <div class="mb-4 text-center">
        <span><i class="fa fa-star text-warning"></i></span>
        <span><i class="fa fa-star text-warning"></i></span>
        <span><i class="fa fa-star text-warning"></i></span>
        <span><i class="fa fa-star-half-o text-warning"></i></span>
        <span><i class="fa fa-star-o text-warning"></i></span>
        </div> --}}
            </div>
          </div>
          <form action="{{ route('rapyd.user.avatar', ['user' => $user]) }}" method="POST"
            enctype="multipart/form-data" id="avatar_form">
            @csrf
            <input type="file" class="form-control form-control-sm" name="avatar" accept=".jpg,.jpeg,.png"
              id="avatar_file">
          </form>
          <form action="{{ route('rapyd.user.avatar.remove', ['user' => $user]) }}" method="POST">
            @csrf
            <button type="submit"
              class="btn btn-block btn-primary mt-5 btn-sm font-weight-bold">Remove Avatar</button>
          </form>
        </div>
      </div>
      {{-- Password Update --}}
      <form method="POST" action="{{ route('rapyd.user.update_pass') }}">
        <div id="password_update_form" class="card card-collapsed">
          @csrf
          <input type="hidden" name="id" value={{ $user->id }}>

          <div class="card-header">
            <div class="card-title">Edit Password</div>
            <div class="card-options">
              <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i
                  class="fe fe-chevron-down"></i></a>
            </div>
          </div>
          <div class="card-body" id="edit_password_wrapper">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input type="password" class="form-control form-control-sm" name="password"
                value="password">
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <input type="password" class="form-control form-control-sm" name="password_confirm"
                value="password">
            </div>
          </div>
          <div class="text-right card-footer">
            <button class="btn btn-primary">Update</button>
          </div>
        </div>
      </form>

      {{-- LIST OF PAYMENT METHODS ATTACHED --}}
      <div class="card card-collapsed" id="payment_method_wrappers">
        <div class="card-header">
          <h3 class="card-title">Payment Methods</h3>
          <div class="card-options">
            <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i
                class="fe fe-chevron-down"></i></a>
          </div>
        </div>

        {{-- PRINCIPAL ATTACHED CREDIT CARDS --}}
        @if ($user->authnet_id)
          @php
            $authnet_profile = AuthnetProfile::fetchProfileSingular($user->authnet_id, true);
            $payment_profile = $authnet_profile->getPaymentProfiles();
            $shipping_profile = $authnet_profile->getShipToList();
          @endphp
          <div class="card-body">
            @if ($payment_profile)
              @foreach ($payment_profile as $profile)
                @php
                  // CustomerPaymentProfileMaskedType
                  $authnet_payment = $profile->getPayment();
                  $profile_id = $profile->getCustomerPaymentProfileId();
                  $is_bank_method = $profile->getPayment()->getBankAccount();

                  if ($is_bank_method === null) {
                    // PaymentMaskedType
                    $authnet_card = $profile->getPayment()->getCreditCard();

                    // CreditCardMaskedType
                    $card_details = [
                      'payment_id' => $profile_id,
                      'customer_id' => $user->authnet_id,
                      'last4' => $authnet_card->getCardNumber(),
                      'type' => $authnet_card->getCardType(),
                    ];
                  } else {
                    $bank_info = [
                      'payment_id' => $profile_id,
                      'customer_id' => $user->authnet_id,
                      'route' => $is_bank_method->getRoutingNumber(),
                      'account' => $is_bank_method->getAccountNumber(),
                    ];
                  }
                @endphp

                <div class="row">
                  {{-- CREDIT CARD INFO --}}
                  <div class="col-12 form-group">
                    @if ($is_bank_method === null)
                      <input type="hidden" value="{{ $card_details['payment_id'] }}" />
                      <input type="hidden" value="{{ $card_details['customer_id'] }}" />
                      <strong>Credit Card</strong>
                      <i class="payment payment-{{ strtolower($card_details['type']) }}"></i><br>
                      {{ $card_details['last4'] }}<br>

                      <strong>Name on Card</strong><br>
                      {{ $user->name_first }} {{ $user->name_last }}<br>

                      <a href="@url('/api/paymentgateway/authnet/removepaymentmethod/'){{ $user->authnet_id }}/{{ $card_details['payment_id'] }}"
                        class="btn btn-primary btn-sm">Remove Card</a>
                    @else
                      <input type="hidden" value="{{ $bank_info['payment_id'] }}" />
                      <input type="hidden" value="{{ $bank_info['customer_id'] }}" />
                      {{-- BANK ACCOUNT INFO --}}
                      <strong>Bank Account Number</strong><br>
                      {{ $bank_info['account'] }}<br>

                      <strong>Routing Number</strong><br>
                      {{ $bank_info['route'] }}<br>

                      <a href="@url('/api/paymentgateway/authnet/removepaymentmethod/'){{ $user->authnet_id }}/{{ $bank_info['payment_id'] }}"
                        class="btn btn-primary btn-sm">Remove Bank</a>
                    @endif
                  </div>
                </div>

                <hr style="margin: .5rem 0">
              @endforeach
            @endif
          </div>
        @endif
      </div>

      @include('rapyd_admin::widgets.shareable-wrapper', ['userId' => $user->id])
    </div>

    <div class="col-lg-9 col-xl-9 col-md-12 col-sm-12" id="col-right">
      @if(!$user->name_first || !$user->name_last || !$user->address_street || !$user->address_city || !$user->address_state || !$user->address_zip)
        <div class="alert alert-danger alert-block">
          <button type="button" class="close" data-dismiss="alert">×</button>
          <strong>
            Please complete the following to gain access to adding additional payment methods.<br>
            @if(!$user->name_first)
              First Name<br>
            @endif
            @if(!$user->name_last)
              Last Name<br>
            @endif
            @if(!$user->address_street)
              Address Street<br>
            @endif
            @if(!$user->address_city)
              Address City<br>
            @endif
            @if(!$user->address_state)
              Address State<br>
            @endif
            @if(!$user->address_zip)
              Address Zip<br>
            @endif
          </strong>
        </div>
      @endif
      <form method="POST" action="{{ route('rapyd.user.update') }}" id="profile" enctype="multipart/form-data">
        @csrf

        <input type="hidden" name="id" value={{ $user->id }}>

        <div class="card" id="basic_info_wrapper">
          {{-- BASIC INFO --}}
          <div class="card-header edit_profile">
            <h3 class="card-title">User Profile</h3>
            <div class="btn btn-sm btn-success" onclick="editProfile()" id="edit_profile_btn">Edit Profile
            </div>
          </div>

          <div class="card-body">
            <div class="row">
              @if (Auth::user()->can('user-update'))
                {{-- IS APPROVED? --}}
                <div class="col-md-4 form-group">
                  <label>Is Approved?</labeL>
                  <select name="is_approved" class="form-control form-control-sm">
                    <option value=0 @if ($user->is_approved === 0) selected @endif>No</option>
                    <option value=1 @if ($user->is_approved === 1) selected @endif>Yes</option>
                  </select>
                </div>

                {{-- USER ROLE ASSIGNMENT --}}
                <div class="col-md-4 form-group">
                  <label>User Role</labeL>
                  <select name="user_role" class="form-control form-control-sm">
                    <option>Select a Role</option>
                    @foreach ($all_user_roles as $role)
                      <option value="{{ $role->name }}" @if ($user->roles->pluck('name')->first() === $role->name) selected @endif>
                        {{ $role->name }}
                      </option>
                    @endforeach
                  </select>
                </div>
                {{-- User Url Slug --}}
                <div class="col-md-4 form-group">
                  <label>Page Url Slug</label>
                  <input name="page_url_slug" class="form-control form-control-sm"
                    value="{{ $user->page_url_slug }}">
                </div>
              @else
                <input type="hidden" name="user_role"
                  value="{{ $user->roles->pluck('name')->first() }}" />
              @endif
              <div class="col-lg-4 col-md-12">
                <div class="form-group">
                  <label for="exampleInputname">First Name</label>
                  <input type="text" class="form-control form-control-sm" name="name_first"
                    placeholder="First Name" value="{{ $user->name_first }}" required>
                </div>
              </div>
              <div class="col-lg-4 col-md-12">
                <div class="form-group">
                  <label for="exampleInputname1">Last Name</label>
                  <input type="text" class="form-control form-control-sm" name="name_last"
                    placeholder="Enter Last Name" value="{{ $user->name_last }}" required>
                </div>
              </div>
              {{-- Usergroup --}}
              <div class="col-sm-4 form-group">
                <label for="usergroup_id">Agency</label>
                <div id="agency_list_wrapper">
                  @if ($user->usergroup())
                    <a type="text" class="mr-2 text-primary"
                      href="@url('/admin/usergroups/profile?group='){{ $user->usergroup()->id }}">{{ $user->usergroup()->name }}</a>
                  @endif
                </div>
              </div>
              <div class="col-lg-4 col-md-12">
                <div class="form-group">
                  <label for="exampleInputEmail1">Email address</label>
                  <input type="email" class="form-control form-control-sm" name="email"
                    placeholder="email address" value="{{ $user->email }}" disabled>
                </div>
              </div>
              <div class="col-lg-4 col-md-12">
                <div class="form-group">
                  <label for="exampleInputnumber">Phone Number</label>
                  <input type="text" class="form-control form-control-sm phone" name="phone_main"
                    placeholder="ph number" value="{{ $user->phone_main }}">
                </div>
              </div>
              {{-- SHOW IN AGENT FINDER? --}}
              <div class="col-md-4 form-group">
                <label>Show in Agent Finder?</labeL>
                <select name="is_agent_finder" class="form-control form-control-sm">
                  <option value=0 @if ($user->is_agent_finder === 0) selected @endif>No</option>
                  <option value=1 @if ($user->is_agent_finder === 1) selected @endif>Yes</option>
                </select>
              </div>
            </div>
          </div>

          {{-- ADDRESS --}}
          <div class="card-header">
            <h3 class="card-title">Address</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="exampleInputname">Street</label>
                  <input type="text" class="form-control form-control-sm" name="address_street"
                    id="address_street" placeholder="Street" value="{{ $user->address_street }}">
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label for="exampleInputname">Street 2</label>
                  <input type="text" class="form-control form-control-sm" name="address_street_2"
                    id="address_street_2" placeholder="Street 2"
                    value="{{ $user->address_street_2 }}">
                </div>
              </div>

              <div class="col-md-4"></div>

              <div class="col-sm-4">
                <div class="form-group">
                  <label for="exampleInputname">City</label>
                  <input type="text" class="form-control form-control-sm" name="address_city"
                    id="address_city" placeholder="City" value="{{ $user->address_city }}">
                </div>
              </div>

              <div class="col-sm-4">
                <div class="form-group">
                  <label for="exampleInputname">State</label>
                  <input type="text" class="form-control form-control-sm" name="address_state"
                    id="address_state" placeholder="State" value="{{ $user->address_state }}">
                </div>
              </div>

              <div class="col-sm-4">
                <div class="form-group">
                  <label for="exampleInputname">Zipcode</label>
                  <input type="text" class="form-control form-control-sm" name="address_zip"
                    id="address_zip" placeholder="Zipcode" value="{{ $user->address_zip }}">
                </div>
              </div>

              <div class="col-sm-4">
                <div class="form-group">
                  <label for="exampleInputname">County</label>
                  <input type="text" class="form-control form-control-sm" name="address_county"
                    id="address_county" placeholder="County" value="{{ $user->address_county }}">
                </div>
              </div>
            </div>
          </div>

          {{-- ADDITIONAL INFO --}}
          <div class="card-header">
            <h3 class="card-title">Additional Info</h3>
          </div>
          <div class="card-body">
            <div class="row">
              @if (auth()->user()->hasrole('Agent'))
                <div class="col-md-12">
                  <div class="mb-6">
                    <p class="font-bold"><b>Allow Public to View My Profile</b></p>
                    <div class="d-flex align-items-center">
                      <div class="d-flex align-items-center">
                        <p class="mt-1 mr-2">Off</p>
                        <div class="material-switch">
                          {{ Form::checkbox('is_agent_finder', '0', true) }}
                          <input id="is_agent_finder" name="is_agent_finder" type="checkbox"
                            value="1" @if ($user->is_agent_finder) checked @endif>
                          <label for="is_agent_finder" class="label-success"></label>
                        </div>
                        <p class="mt-1 ml-2">On</p>
                      </div>
                      <p class="mt-1 ml-4">
                        <a href="@url('/agent/profile/'){{ $user->page_url_slug }}" class="mr-2">View
                          My Public Profile</a>
                      </p>
                    </div>
                  </div>
                </div>
              @endif
              <div class="col-md-12">
                <div class="form-group">
                  <label>
                    {{ $user->roles->contains('name', 'Agent') ? 'About Your Agency' : 'About Me' }}
                  </label>
                  <textarea name="bio" class="form-control" rows="6"
                    placeholder="...">{{ $user->bio }}</textarea>
                </div>
              </div>
              <div class="col-lg-4 col-md-12">
                <div class="form-group">
                  <label>Website</label>
                  <input name="social_website" class="form-control form-control-sm"
                    placeholder="http://mywebsite.com" value="{{ $user->social_website }}">
                </div>
              </div>
              <div class="col-lg-4 col-md-12">
                <div class="form-group">
                  <label>Twitter Profile</label>
                  <input name="social_twitter" class="form-control form-control-sm"
                    placeholder="username" value="{{ $user->social_twitter }}">
                </div>
              </div>
              <div class="col-lg-4 col-md-4">
                <div class="form-group">
                  <label>Facebook Page</label>
                  <input name="social_facebook" class="form-control form-control-sm"
                    placeholder="pagelink" value="{{ $user->social_facebook }}">
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button id="save_edited_profile" class="mt-1 btn btn-success">Save</button>
            <a href="#" class="mt-1 btn btn-danger" onclick="editProfile()">Cancel</a>
            <a class="mt-1 btn btn-danger" href="@url('/api/user/delete/'){{ $user->id }}">Remove</a>
          </div>
        </div>
      </form>

      {{-- ADD PAYMENT METHODS --}}
      @if($user->name_first && $user->name_last && $user->address_street && $user->address_city && $user->address_state && $user->address_zip)
        {{-- CREDIT CARD --}}
        <div id="credit_card_wrapper" class="card card-collapsed">
          <div class="card-header">
            <h3 class="card-title">Add A Credit Card</h3>
            <div class="card-options">
              <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i
                  class="fe fe-chevron-down"></i></a>
            </div>
          </div>

          <div class="card-body">
            <p type="button" class="mb-1 btn btn-success btn-sm" onclick="copy_principal_to_card()">
              Copy Profile Info
            </p>

            <form method='POST' action='/api/paymentgateway/authnet/addpaymentmethod'
              enctype="multipart/form-data" class="row">
              @csrf
              <input type="hidden" name="billing_profile_id" value={{$user->authnet_id}}>
              <input type="hidden" name="billing_user_id" value={{$user->id}}>
              <input type="hidden" name="is_usergroup" value="0">

              {{-- BILLING INFO --}}
              <div class="form-group col-6">
                <label for="billing_name_first">First Name</label>
                <input type="text" class="form-control form-control-sm" required id="billing_name_first"
                  name='billing_name_first'>
              </div>

              <div class="form-group col-6">
                <label for="billing_name_last">Last Name</label>
                <input type="text" class="form-control form-control-sm" id="billing_name_last"
                  name='billing_name_last'>
              </div>

              <div class="form-group col-sm-12">
                <label for="billing_address">Address</label>
                <input type="text" class="form-control form-control-sm" required id="billing_address"
                  name='billing_address'>
              </div>

              <div class="form-group col-sm-5">
                <label for="billing_city">City</label>
                <input type="text" class="form-control form-control-sm" required id="billing_city"
                  name='billing_city'>
              </div>
              <div class="form-group col-sm-3">
                <label for="billing_state">State</label>
                <input type="text" class="form-control form-control-sm" required id="billing_state"
                  name='billing_state'>
              </div>

              <div class="form-group col-sm-4">
                <label for="billing_zip">Zip</label>
                <input type="text" class="form-control form-control-sm" required id="billing_zip"
                  name='billing_zip'>
              </div>

              {{-- CARD INFO --}}
              <div class="form-group col-sm-9">
                <label for="billing_card_number">Card Number</label>
                <input type="text" class="form-control form-control-sm" required id="billing_card_number"
                  name='billing_card_number'>
              </div>
              <div class="form-group col-sm-3">
                <label for="billing_exp">Exp Date</label>
                <input type="text" class="form-control form-control-sm" id="billing_exp" name='billing_exp'
                  placeholder="MM/YY" required>
              </div>

              {{--
              <div class="form-group col-sm-3">
                <label for="billing_cvv">CVV</label>
                <input type="text" class="form-control" required id="billing_cvv" name='billing_cvv'>
              </div>
              --}}

              <div class="col-sm-12">
                <input type='submit' class='btn btn-primary' value='Add Card'>
              </div>
            </form>
          </div>
        </div>

        {{-- BANK ACCOUNT --}}
        <div id="bank_account_wrapper" class="card card-collapsed">
          <div class="card-header">
            <h3 class="card-title">Add a Bank Account</h3>
            <div class="card-options">
              <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i
                  class="fe fe-chevron-down"></i></a>
            </div>
          </div>

          <div class="card-body">
            <form method='POST' action='/api/paymentgateway/authnet/addpaymentmethod'
              enctype="multipart/form-data" class="row">
              @csrf
              <input type="hidden" required id="billing_profile_id" name='billing_profile_id'
                value={{ $user->authnet_id }}>
              <input type="hidden" required id="billing_user_id" name='billing_user_id'
                value={{ $user->id }}>
              <input type="hidden" name="no_shipping" value="true">
              <input type="hidden" name='billing_name_first' value="{{$user->name_first}}">
              <input type="hidden" name='billing_name_last' value="{{$user->name_last}}">

              {{-- BILLING INFO --}}
              <div class="form-group col-sm-12">
                <label for="name_on_account">Name on Account</label>
                <input type="text" class="form-control form-control-sm" required id="name_on_account"
                  name='name_on_account'>
              </div>

              <div class="form-group col-sm-6">
                <label for="account_number">Account #</label>
                <input type="text" class="form-control form-control-sm" id="account_number"
                  name='account_number'>
              </div>
              <div class="form-group col-sm-6">
                <label for="routing_number">Routing #</label>
                <input type="text" class="form-control form-control-sm" id="routing_number"
                  name='routing_number'>
              </div>
              <div class="form-group col-sm-12">
                <label for="bank_name">Bank Name</label>
                <div class="btn btn-sm btn-primary" onclick="get_bank_name()">Get Name from Routing #</div>
                <input type="text" class="form-control form-control-sm" id="bank_name" name='bank_name'>
              </div>


              <div class="col-sm-12">
                <input type='submit' class='btn btn-primary' value='Add Bank Account'>
              </div>
            </form>
          </div>
        </div>
      @endif
    </div>
  </div>
  
  <script src="/modules/User/Resources/Admin/js/user_profile.js"></script>
  <script>
    function copy_principal_to_card() {
      document.getElementById(`billing_user_id`).value = {{ $user->id }};

      @if ($user->authnet_id)
    document.getElementById(`billing_profile_id`).value = {{ $user->authnet_id }};
  @else
    document.getElementById(`billing_profile_id`).value = "";
  @endif

      document.getElementById(`billing_name_first`).value = "{{ $user->name_first }}";
      document.getElementById(`billing_name_last`).value = "{{ $user->name_last }}";

      document.getElementById(`billing_address`).value = "{{ $user->address_street }}";
      document.getElementById(`billing_city`).value = "{{ $user->address_city }}";
      document.getElementById(`billing_state`).value = "{{ $user->address_state }}";
      document.getElementById(`billing_zip`).value = "{{ $user->address_zip }}";
    }

    var tour = [{
        element: '#basic_info_wrapper',
        title: 'Profile Overview',
        description: "Here is where you find all the inputted information for your agent profile in the system.",
      },
      {
        element: '#edit_profile_btn',
        title: 'Edit Your Profile',
        description: 'Need to edit some information on your profile? Click here to do so.',
      },
      {
        element: '#basic_info_wrapper',
        title: 'Review Edit Profile',
        description: "Now you can edit all fields as need be.",
      },
      {
        element: '#save_edited_profile',
        title: 'Save your Edits',
        description: "Don't forget to save your updated info. Or you may cancel the changes."
      },
      {
        element: '#password_update_form',
        title: 'Update Password',
        description: "Need to update your password? Simply type a new one in and click 'Update'. Note that will not update your profile info, this will only update your password.",
      },
      {
        element: '#payment_method_wrappers',
        title: 'Attached Payment Methods',
        description: "As payment methods are added to your agent profile they will show up in this box for quick reference. Note that these payment methods will also show up in the agency profile page also, along with any other agents of the same agency and attached agency payment methods.",
      },
      {
        element: '#credit_card_wrapper',
        title: 'Add a Credit Card',
        description: "Adding a new credit card is simple to do and is secured through Authorize/net's firewall and security measures allowing you to rest easy that your information is safe.",
      },
      {
        element: '#bank_account_wrapper',
        title: 'Add a Bank Account',
        description: "Prefer to use an ACH method? We have that covered as well, also secured behind Authorize/net's firewall and security measures.",
      },
      {
        element: '#agency_list_wrapper',
        title: 'View Agency Profile',
        description: "Take a look at your agency profile by clicking here.",
      }
      @if ($user->usergroup())
  ,{
    title: 'Continue Tour?',
    description: `
    <p>
      You can continue to tour or stop now. Remember, you can always get help from the dropdown menu if information is
      needed in the future.
    </p>
    <button class="btn btn-success"
      onclick="(function(){ window.location.href='/admin/usergroups/profile?group={{ $user->usergroup()->id }}';})();">Continue
      Tour</button>
    <button class="btn btn-danger" onclick="(function(){ $('.gc-close').click();})();">End Tour</button>
  `,
  }
  @endif
    ];

    GuideChimp.extend(guideChimpPluginPlaceholders, {
      template: '%*%'
    });
    var guideChimp = GuideChimp(tour);

    // CALLBACK ACTIONS FOR SPECIFIC STEPS OF TOUR
    guideChimp.on('onBeforeChange', (to, from) => {
      if (to.title == 'Edit Your Profile') {
        $('#edit_profile_btn').click();
      } else if (from.title == 'Save your Edits') {
        $('#edit_profile_btn').click();
      } else if (to.title == 'Add a Credit Card') {
        $('#credit_card_wrapper .card-options a').click();
      } else if (to.title == 'Add a Bank Account') {
        $('#credit_card_wrapper .card-options a').click();
        $('#bank_account_wrapper .card-options a').click();
      } else if (from.title == 'Add a Bank Account') {
        $('#bank_account_wrapper .card-options a').click();
      }
    });

    // START GUIDED TOUR
    @if ($tour_check) guideChimp.start(); @endif

  </script>
@endif

@pageloading