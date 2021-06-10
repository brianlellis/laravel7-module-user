@php
use Rapyd\Ecomm\Authnet\AuthnetProfile;
use Rapyd\Ecomm\Authnet\OrderHelper;
use Swis\Laravel\Fulltext\Search;

$tour_check = \Rapyd\Tours::first_visit();

if (request('group')) {
  $own_profile = false;
  $usergroup = \RapydUsergroups::show(Request::get('group'));
} else {
  $own_profile = true;
  $usergroup = \RapydUsergroups::show(auth()->user()->id);
}

$tax_id_type = [
  [
    'label' => 'EIN: (xx-xxxxxxx)',
    'value' => 'EIN',
  ],
  [
    'label' => 'SSN: (xxx-xx-xxxx)',
    'value' => 'SSN',
  ],
];

if ($search_term = Request::get('agent_search')) {
  $search = new Search();
  $result = $search->runForClass($search_term, App\User::class)->pluck('indexable_id');
  $agents = App\User::findMany($result);
}

$group_types = \DB::table('usergroup_types')->get();
@endphp

@if (!$usergroup->producer_agreement_ip && $usergroup->type->description === 'agency')
  <div class="alert alert-danger alert-block">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <strong>No Producer Agreement</strong>
  </div>
@endif

@if(!$usergroup->name || !$usergroup->address_street || !$usergroup->address_city || !$usergroup->address_state || !$usergroup->address_zip)
  <div class="alert alert-danger alert-block">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <strong>
      Please complete the following to gain access to adding additional payment methods.<br>
      @if(!$usergroup->name)
        Name<br>
      @endif
      @if(!$usergroup->address_street)
        Address Street<br>
      @endif
      @if(!$usergroup->address_city)
        Address City<br>
      @endif
      @if(!$usergroup->address_state)
        Address State<br>
      @endif
      @if(!$usergroup->address_zip)
        Address Zip<br>
      @endif
    </strong>
  </div>
@endif

@if (Auth::user()->can('usergroup-view') || $own_profile)
  <div class="row">
    {{-- MAIN CONTENT --}}
    @if (auth()->user()->hasanyrole('Developer') && $usergroup->type->description === 'agency')
      <div class="col-sm-12">
        {{-- CHECK FOR producer_agreement_ip ELSE Deactivate / Activate Usergroup --}}
        @if ($usergroup->producer_agreement_ip)
          @if ($usergroup->is_active)
            <a href="@url('/api/usergroup/deactivate/'){{ $usergroup->id }}" class="btn btn-primary pull-right">
              Deactivate Agency
            </a>
          @else
            <a href="@url('/api/usergroup/activate/'){{ $usergroup->id }}" class="btn btn-primary pull-right">
              Activate Agency
            </a>
          @endif
        @else
          <a href="@url('/api/usergroup/producer/override/'){{ $usergroup->id }}" class="btn btn-danger pull-right mb-4 ml-2">
            Override Producer Agreement
          </a>
          <a href="@url('/api/usergroup/producer/send/'){{ $usergroup->id }}" class="btn btn-primary pull-right mb-4">
            Send Producer Agreement
          </a>
        @endif
      </div>
    @endif
    <div class="col-sm-12">
      <div class="panel panel-primary">
        <div class="tab_wrapper first_tab">
          <ul class="tab_list">
            <li id="tab_overview" class="active" rel="tab_1_1" onclick="set_active_tab(this)"
              data-tab="Overview">Overview</li>
            @if($usergroup->type->description === 'agency')
              <li id="tab_agents" rel="tab_1_2" onclick="set_active_tab(this)" data-tab="Agents">Agents</li>
              <li id="tab_policies" rel="tab_1_3" onclick="set_active_tab(this)" data-tab="Policies">Policies
              </li>
              <li id="tab_payment_methods" rel="tab_1_4" onclick="set_active_tab(this)"
              data-tab="Payment Methods">Payment Methods</li>
            @endif
          </ul>

          <div class="content_wrapper">
            {{-- AGENCY OVERVIEW --}}
            <div class="tab_content active first tab_1_1" title="tab_1_1" style="display: block;">
              <div class="row">
                {{-- LEFT SIDE BAR --}}
                <div class="col-lg-3 col-md-12" id="col-left">
                  <div class="card">
                    <div class="card-body">
                      <div class="text-center">
                        <div class="userprofile">
                          @php
                            $avatar_path = $usergroup->avatar ? $usergroup->avatar : SettingsSite::get('default_user_avatar');
                          @endphp
                          <img src="{{ asset($avatar_path) }}" alt="User Avatar"
                            class="userpic brround">
                          <h3 class="username text-dark mb-2">{{ $usergroup->name ?? '' }}</h3>
                        </div>
                      </div>
                      <div>
                        <form
                          action="{{ route('rapyd.usergroup.avatar', ['usergroup' => $usergroup]) }}"
                          method="POST" enctype="multipart/form-data" id="avatar_form">
                          @csrf
                          <input type="file" class="form-control form-control-sm"
                            name="avatar" accept=".jpg,.jpeg,.png" id="avatar_file">
                        </form>
                      </div>
                    </div>
                  </div>

                  @include('rapyd_admin::widgets.shareable-wrapper', ['userId' => $usergroup->id])
                </div>

                <div class="col-lg-9 col-md-12" id="col-right">
                  <form method="POST" action="{{ route('rapyd.usergroup.update', $usergroup->id) }}"
                    id="profile" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value={{ $usergroup->id }}>

                    <div class="card">
                      {{-- BASIC INFO --}}
                      <div class="card-header edit_profile">
                        <h3 class="card-title">
                          {{ ucfirst($usergroup->type->description) ?? '' }} Profile</h3>
                        <div class="btn btn-sm btn-success" onclick="editProfile()">Edit
                          {{ ucfirst($usergroup->type->description) ?? 'Profile' }}</div>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          @if (Auth::user()->can('user-update'))
                            <div class="col-sm-6 form-group">
                              <label for="exampleInputnumber">Usergroup Type</label>
                              <select class="form-control" name="usergroup_type_id">
                                @foreach ($group_types as $type)
                                  <option value={{ $type->id }} @if ($type->id == $usergroup->usergroup_type_id) selected @endif>
                                    {{ ucfirst($type->description) }}
                                  </option>
                                @endforeach
                              </select>
                            </div>

                            {{-- User Url Slug --}}
                            <div class="col-sm-6 form-group">
                              <label>Page Url Slug</label>
                              <input name="page_url_slug" class="form-control"
                                @if($usergroup->page_url_slug)
                                  value="{{ $usergroup->page_url_slug }}"
                                @endif
                              >
                            </div>
                          @endif

                          {{-- REFERRAL CODE --}}
                          <div class="col-sm-6 form-group">
                            <label>Commission Referral Code</label>
                            <input name="referral_code" class="form-control"
                              @if($usergroup->referral_code)
                                value="{{ $usergroup->referral_code }}"
                              @endif
                            >
                          </div>

                          <div class="col-lg-6 col-md-12">
                            <div class="form-group">
                              <label for="exampleInputname">Name</label>
                              <input type="text" class="form-control" name="name"
                                placeholder="Name" 
                                @if($usergroup->name)
                                  value="{{ $usergroup->name }}"
                                @endif
                                required>
                            </div>
                          </div>
                          <div class="col-lg-6 col-md-12">
                            <div class="form-group">
                              <label for="exampleInputnumber">Phone Number</label>
                              <input type="text" class="form-control phone"
                                name="phone_main" placeholder="ph number"
                                @if($usergroup->phone_main)
                                  value="{{ $usergroup->phone_main }}"
                                @endif
                              >
                            </div>
                          </div>
                        </div>
                      </div>

                      {{-- ADDRESS --}}
                      <div class="card-header">
                        <h3 class="card-title">Address</h3>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-lg-4 col-sm-12">
                            <div class="form-group">
                              <label for="exampleInputname">Street</label>
                              <input type="text" class="form-control"
                                name="address_street" id="address_street"
                                placeholder="Street"
                                value="{{ $usergroup->address_street }}">
                            </div>
                          </div>

                          <div class="col-lg-4 col-sm-12">
                            <div class="form-group">
                              <label for="exampleInputname">Street 2</label>
                              <input type="text" class="form-control"
                                name="address_street_2" id="address_street_2"
                                placeholder="Street 2"
                                value="{{ $usergroup->address_street_2 }}">
                            </div>
                          </div>

                          <div class="col-lg-4"></div>

                          <div class="col-lg-4 col-sm-12">
                            <div class="form-group">
                              <label for="exampleInputname">City</label>
                              <input type="text" class="form-control" name="address_city"
                                id="address_city" placeholder="City"
                                value="{{ $usergroup->address_city }}">
                            </div>
                          </div>

                          <div class="col-lg-4 col-sm-12">
                            <div class="form-group">
                              <label for="exampleInputname">State</label>
                              <input type="text" class="form-control" name="address_state"
                                id="address_state" placeholder="State"
                                value="{{ $usergroup->address_state }}">
                            </div>
                          </div>

                          <div class="col-lg-4 col-sm-12">
                            <div class="form-group">
                              <label for="exampleInputname">Zipcode</label>
                              <input type="text" class="form-control" name="address_zip"
                                id="address_zip" placeholder="Zipcode"
                                value="{{ $usergroup->address_zip }}">
                            </div>
                          </div>

                          <div class="col-12">
                            <div class="form-group">
                              <label for="exampleInputname">County</label>
                              <input type="text" class="form-control" name="address_county"
                                id="address_county" placeholder="County"
                                value="{{ $usergroup->address_county }}">
                            </div>
                          </div>
                        </div>
                      </div>

                      {{-- Tax INFO --}}
                      <div class="card-header">
                        <h3 class="card-title">Tax Info</h3>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                              <label class="form-label">Renewal Billing</label>
                              @php
                                $allow_renewal = ['Direct', 'Agent'];
                              @endphp
                              <select name="allow_renewal" id="" class="form-control">
                                <option value="">Renewal Billing Type ( Direct / Agent )
                                </option>
                                @foreach ($allow_renewal as $item)
                                  <option value="{{ $item }}" @if ($usergroup && $usergroup->allow_renewal === $item) selected @endif>
                                    {{ $item }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                              @php
                                $entity_type = [
                                  'Corporation',
                                  'Sole Proprietor',
                                  'Partnership',
                                  'LLC - Single Member',
                                  'LLC - C
                                                                                  Corporation',
                                  'LLC - S Corporation',
                                  'LLC - Partnership',
                                ];
                              @endphp
                              <label class="form-label">Entity Type</label>
                              <select name="entity_type" class="form-control">
                                <option value="" class="placeholder">Entity Type
                                </option>
                                @foreach ($entity_type as $item)
                                  <option value="{{ $item }}" @if ($usergroup && $usergroup->entity_type === $item) selected @endif>
                                    {{ $item }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                          <div class="col-md-8 col-sm-12">
                            <div class="form-group">
                              <label class="form-label">License Number</label>
                              <input name="license" class="form-control"
                                placeholder="License Number"
                                value="{{ $usergroup->license ?? old('license') }}">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-4 col-sm-12">
                            <div class="form-group">

                              <label class="form-label">Tax ID Format</label>
                              <select name="tax_id_type" class="form-control">
                                <option value="">Format Type ( ENI / SSN )</option>
                                @foreach ($tax_id_type as $item)
                                  <option value="{{ $item['value'] }}" @if ($usergroup && $usergroup->tax_id_type === $item['value']) selected @endif>
                                    {{ $item['label'] }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                          <div class="col-md-8 col-sm-12">
                            <div class="form-group">
                              <label class="form-label">Tax ID</label>
                              <input name="tax_id" class="form-control"
                                placeholder="Tax ID"
                                value="{{ $usergroup->tax_id ?? old('tax_id') }}">
                            </div>
                          </div>
                        </div>
                      </div>


                      {{-- ADDITIONAL INFO --}}
                      <div class="card-header">
                        <h3 class="card-title">ADDITIONAL Info</h3>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="form-group col-12">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="6"
                              placeholder="Bio.........">{{ $usergroup->bio }}</textarea>
                          </div>
                          <div class="form-group col-lg-4 col-md-12">
                            <label class="form-label">Website</label>
                            <input name="social_website" class="form-control"
                              placeholder="http://mywebsite.com"
                              value="{{ $usergroup->social_website }}">
                          </div>
                          <div class="form-group col-lg-4 col-md-12">
                            <label class="form-label">Twitter Profile</label>
                            <input name="social_twitter" class="form-control"
                              placeholder="username"
                              value="{{ $usergroup->social_twitter }}">
                          </div>
                          <div class="form-group col-lg-4 col-md-12">
                            <label class="form-label">Facebook Page</label>
                            <input name="social_facebook" class="form-control"
                              placeholder="pagelink"
                              value="{{ $usergroup->social_facebook }}">
                          </div>
                        </div>
                      </div>

                      {{-- Save --}}
                      <div class="card-footer">
                        <button class="btn btn-success mt-1">Save</button>
                        <a href="#" class="btn btn-danger mt-1"
                          onclick="editProfile()">Cancel</a>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            @if($usergroup->type->description === 'agency')
              {{-- AGENCY AGENTS --}}
              <div id="agency_agents_wrapper" class="tab_content tab_1_2" title="tab_1_2">
                {{-- Users in the Group --}}
                <div class="card current-agents-container">
                  <div class="card-header">
                    <p>
                      Attached Users ({{ count($usergroup->users) }})
                    </p>
                  </div>
                  <div class="card-body">
                    @foreach ($usergroup->users as $user)
                      <div class="agent-content {{auth()->user()->id === $user->id ? 'own-user' : null}}">
                        <a href="@url('/admin/user/profile?user_id='){{ $user->id }}" target="_blank">
                          {{ $user->name_last }}, {{ $user->name_first }}<br>
                        </a>
                        @if (auth()->user()->id !== $user->id)
                          <!-- Button trigger modal -->
                          <button type="button"
                            class="btn btn-sm btn-danger btn-block mt-2 text-center"
                            data-toggle="modal" data-target="#deleteModal{{ $user->id }}">
                            Remove User
                          </button>
                        @endif
                      </div>
                      <!-- Modal -->
                      <div class="modal fade" id="deleteModal{{ $user->id }}" tabindex="-1"
                        role="dialog" aria-labelledby="deleteModal{{ $user->id }}Title"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="exampleModalLongTitle">Transfer
                                policies to</h5>
                              <button type="button" class="close" data-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                              </button>
                            </div>
                            <form action="/api/usergroup/removeagent" method="POST">
                              @csrf
                              <div class="modal-body">
                                <input type="hidden" name="user"
                                  value="{{ $user->id }}">
                                <input type="hidden" name="usergroup"
                                  value="{{ $usergroup->id }}">
                                <div class="form-group">
                                  <label for="transfer_agent">Select Agent</label>
                                  <select name="transfer_agent" id="transfer_agent"
                                    class="form-control">
                                    @foreach ($usergroup->users as $new_agent)
                                      @if ($user->id !== $new_agent->id)
                                        <option value="{{ $new_agent->id }}">
                                          {{ $new_agent->name_last }},
                                          {{ $new_agent->name_first }}
                                        </option>
                                      @endif
                                    @endforeach
                                  </select>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="submit" class="btn btn-danger">Remove
                                  {{ $user->name_last }},
                                  {{ $user->name_first }}</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>

                {{-- MANUALLY CREATE THE USER --}}
                <form method="POST" action="{{ route('rapyd.user.register') }}" class="row">
                  @csrf
                  <input type="hidden" name="custom_route" value="{{ request()->getRequestUri() }}">
                  <input type="hidden" name="role_name" value="Agent">
                  <input type="hidden" name="usergroup_id" value="{{ $usergroup->id }}">
                  <input type="hidden" name="phone_main" value="{{ $usergroup->phone_main }}">
                  <input type="hidden" name="address_street" value="{{ $usergroup->address_street }}">
                  <input type="hidden" name="address_street_2"
                    value="{{ $usergroup->address_street_2 }}">
                  <input type="hidden" name="address_city" value="{{ $usergroup->address_city }}">
                  <input type="hidden" name="address_state" value="{{ $usergroup->address_state }}">
                  <input type="hidden" name="address_zip" value="{{ $usergroup->address_zip }}">

                  <div class="col-sm-12">
                    <div class="card">
                      {{-- BASIC INFO --}}
                      <div class="card-header">
                        <h3 class="card-title">Add Agent</h3>
                      </div>

                      <div class="card-body">
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="row">
                              <div class="col-sm-4">
                                <div class="form-group">
                                  <label for="exampleInputname">First Name</label>
                                  <input type="text" class="form-control"
                                    name="name_first" placeholder="First Name" required>
                                </div>
                              </div>
                              <div class="col-sm-4">
                                <div class="form-group">
                                  <label for="exampleInputname1">Last Name</label>
                                  <input type="text" class="form-control" name="name_last"
                                    placeholder="Enter Last Name" required>
                                </div>
                              </div>
                              <div class="col-sm-4">
                                <div class="form-group">
                                  <label for="exampleInputEmail1">Email address</label>
                                  <input type="email" class="form-control" name="email"
                                    placeholder="email address" required>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="card-footer">
                        <a href="@url('admin/user/dashboard')" class="btn btn-danger mt-1">Cancel</a>
                        <button type="submit" class="btn btn-success mt-1">Save</button>
                      </div>
                    </div>
                  </div>
                </form>

                {{-- SEARCH TO ADD NEW USERS TO GROUP --}}
                <div class="card">
                  <div class="card-body">
                    <div style="display: flex;">
                      {{-- AGENT SEARCH FUNCTION --}}
                      <input id="search_agent_input" type="text" class="form-control"
                        placeholder="Search for agent" style="width: 70%; padding: 15px;">
                      <a id="search_agent_submit" class="btn btn-primary" type="button"
                        style="width: 30%; border-top-left-radius: 0px; border-bottom-left-radius: 0px;">
                        Search for Agent
                      </a>
                    </div>

                    {{-- AGENT SEARCH RESULTS --}}
                    <div style="max-height: 200px; overflow-y: scroll;">
                      @if (Request::get('agent_search'))
                        @dashboard_table('Action, Email,')
                        @foreach ($agents as $user)
                          @if ($user->hasanyrole('Underwriter|Agent|Developer'))
                            <tr style="font-size: 12px;">
                              <td style="width:10%;">
                                @php
                                  $cur_usergroup_id = Request::get('group');
                                  $new_url = "/api/usergroup/adduser/{$cur_usergroup_id}/{$user->id}";
                                @endphp
                                <a href="{{ $new_url }}"
                                  class="btn btn-sm btn-primary">Add</a>
                              </td>
                              <td style="width:40%;">
                                {{ $user->email }}
                              </td>
                            </tr>
                          @endif
                        @endforeach
                        @end_dashboard_table
                      @endif
                    </div>
                  </div>
                </div>
              </div>

              {{-- AGENCY POLICIES --}}
              <div id="agency_policies_wrapper" class="tab_content tab_1_3" title="tab_1_3">
                {{-- ALL POLICIES ATTACHED TO GROUP --}}
                <div class="card">
                  <div class="card-header">
                    Attached Policies
                  </div>
                  <div class="card-body" style="display: flex; flex-wrap: wrap;">
                    <div class="table-responsive">
                      <table class="table card-table table-vcenter text-nowrap  align-items-center">
                        <thead class="thead-light">
                          <tr>
                            <th>ID #</th>
                            <th>Agent</th>
                            <th>Business</th>
                            <th>Status</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach ($usergroup->users as $user)
                            @foreach ($user->created_policies->sortBy('id') as $policy)
                              <tr>
                                <td>{{ $policy->id }}</td>
                                <td>{{ $policy->agent->full_name() }}</td>
                                <td>{{ $policy->business->name ?? '' }}</td>
                                <td>{{ $policy->status->name ?? '' }}</td>
                                <td>
                                  @if ($policy->js_spa_history && $policy->status->id < 5)
                                    <div class="btn-group" role="group">
                                      <a target="_blank" @if (auth()->user()->hasanyrole('Developer|Underwriter')) href="@url('/admin/bond/policies/edit?policy_id='){{ $policy->id }}"
                                  @else
                                        href="@url('/bondquote?key='){{ $policy->access_key }}" @endif title="History"
                                        class="btn btn-sm btn-primary history">
                                        Edit
                                      </a>
                                    </div>
                                  @else
                                    <a href="@url('/admin/agent/view-policy?key='){{ $policy->access_key }}"
                                      title="View Policy"
                                      class="btn btn-sm btn-success history">
                                      View
                                    </a>
                                  @endif
                                </td>
                              </tr>
                            @endforeach
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              @if($usergroup->name && $usergroup->address_street && $usergroup->address_city && $usergroup->address_state && $usergroup->address_zip)
                {{-- AGENCY PAYMENT METHODS --}}
                <div id="agency_payment_methods_wrapper" class="tab_content tab_1_4" title="tab_1_4">
                  {{-- ATTACHED METHODS --}}
                  <div class="card">
                    <div class="card-header">
                      <h3 class="card-title">Payment Methods</h3>
                      <div class="card-options">
                        <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i
                            class="fe fe-chevron-down"></i></a>
                      </div>
                    </div>

                    {{-- AGENCY ATTACHED PAYMENT METHODS --}}
                    @if ($usergroup->authnet_id)
                      @php
                        $authnet_profile = AuthnetProfile::fetchProfileSingular($usergroup->authnet_id, true);
                        $payment_profile = $authnet_profile->getPaymentProfiles();
                        $shipping_profile = $authnet_profile->getShipToList();
                      @endphp

                      @if ($payment_profile)
                        <div class="card-body">
                          <h4 style="font-weight: 900">Agency</h4>
                          <div class="row">
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
                                    'customer_id' => $usergroup->authnet_id,
                                    'last4' => $authnet_card->getCardNumber(),
                                    'expiration' => $authnet_card->getExpirationDate(),
                                    'type' => $authnet_card->getCardType(),
                                  ];
                                } else {
                                  $bank_info = [
                                    'payment_id' => $profile_id,
                                    'customer_id' => $usergroup->authnet_id,
                                    'route' => $is_bank_method->getRoutingNumber(),
                                    'account' => $is_bank_method->getAccountNumber(),
                                  ];
                                }
                              @endphp

                              {{-- CREDIT CARD INFO --}}
                              <div class="col-3 form-group">
                                @if ($is_bank_method === null)
                                  <input type="hidden"
                                    value="{{ $card_details['payment_id'] }}" />
                                  <input type="hidden"
                                    value="{{ $card_details['customer_id'] }}" />
                                  <strong>Credit Card</strong>
                                  <i
                                    class="payment payment-{{ strtolower($card_details['type']) }}"></i><br>
                                  {{ $card_details['last4'] }}<br>

                                  <a href="@url('/api/paymentgateway/authnet/removepaymentmethod/'){{ $usergroup->authnet_id }}/{{ $card_details['payment_id'] }}"
                                    class="btn btn-primary btn-sm">Remove Card</a>
                                @else
                                  <input type="hidden"
                                    value="{{ $bank_info['payment_id'] }}" />
                                  <input type="hidden"
                                    value="{{ $bank_info['customer_id'] }}" />
                                  {{-- BANK ACCOUNT INFO --}}
                                  <strong>Bank Account Number</strong><br>
                                  {{ $bank_info['account'] }}<br>

                                  <strong>Routing Number</strong><br>
                                  {{ $bank_info['route'] }}<br>

                                  <a href="@url('/api/paymentgateway/authnet/removepaymentmethod/'){{ $usergroup->authnet_id }}/{{ $bank_info['payment_id'] }}"
                                    class="btn btn-primary btn-sm">Remove Bank</a>
                                @endif
                              </div>

                              <hr style="margin: .5rem 0">
                            @endforeach
                          </div>
                        </div>
                      @endif
                    @endif

                    {{-- AGENCY ATTACHED PAYMENT METHODS FROM AGENTS --}}
                    @foreach ($usergroup->users as $user)
                      @if ($user->authnet_id)
                        @php
                          $authnet_profile = AuthnetProfile::fetchProfileSingular($user->authnet_id, true);
                          $payment_profile = $authnet_profile->getPaymentProfiles();
                          $shipping_profile = $authnet_profile->getShipToList();
                        @endphp

                        @if ($payment_profile)
                          <div class="card-body">
                            <h4 style="font-weight: 900">Agent: {{ $user->full_name() }}</h4>
                            <div class="row">
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

                                {{-- CREDIT CARD INFO --}}
                                <div class="col-3 form-group">
                                  <input type="hidden"
                                    value="{{ $card_details['payment_id'] }}" />
                                  <input type="hidden"
                                    value="{{ $card_details['customer_id'] }}" />

                                  @if ($is_bank_method === null)
                                    <strong>Credit Card</strong>
                                    <i
                                      class="payment payment-{{ strtolower($card_details['type']) }}"></i><br>
                                    {{ $card_details['last4'] }}<br>

                                    <a href="@url('/api/paymentgateway/authnet/removepaymentmethod/'){{ $user->authnet_id }}/{{ $card_details['payment_id'] }}"
                                      class="btn btn-primary btn-sm">Remove Card</a>
                                  @else
                                    {{-- BANK ACCOUNT INFO --}}
                                    <strong>Bank Account Number</strong><br>
                                    {{ $bank_info['account'] }}<br>

                                    <strong>Routing Number</strong><br>
                                    {{ $bank_info['route'] }}<br>

                                    <a href="@url('/api/paymentgateway/authnet/removepaymentmethod/'){{ $user->authnet_id }}/{{ $bank_info['payment_id'] }}"
                                      class="btn btn-primary btn-sm">Remove Bank</a>
                                  @endif
                                </div>

                                <hr style="margin: .5rem 0">
                              @endforeach
                            </div>
                          </div>
                        @endif
                      @endif
                    @endforeach
                  </div>

                  {{-- ADD A PAYMENT METHOD --}}
                  <div id="credit_card_wrapper">
                    <div class="card card card-collapsed">
                      <div class="card-header">
                        <h3 class="card-title">Add a Credit Card</h3>
                        <div class="card-options">
                          <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i
                              class="fe fe-chevron-down"></i></a>
                        </div>
                      </div>

                      <div class="card-body">
                        <form method='POST' action='/api/paymentgateway/authnet/addpaymentmethod'
                          enctype="multipart/form-data" class="row">
                          @csrf
                          <input type="hidden" name='billing_profile_id' value="{{$usergroup->authnet_id}}">
                          <input type="hidden" name='billing_usergroup_id' value="{{$usergroup->id}}">

                          {{-- BILLING INFO --}}
                          <div class="form-group col-12">
                            <label for="billing_company">
                              - If there is a need to attach a payment directly to the agent kindly do so in the agent profile page and not here. This method attaches the payment method to the agency itself.<br><br>
                              Agency Name
                            </label>
                            <input type="text" class="form-control" required id="billing_company"
                              name='billing_company' value="{{$usergroup->name}}">
                          </div>

                          <div class="form-group col-sm-12">
                            <label for="billing_address">Address</label>
                            <input type="text" class="form-control" required id="billing_address"
                              name='billing_address' value="{{$usergroup->address_street}}">
                          </div>

                          <div class="form-group col-sm-5">
                            <label for="billing_city">City</label>
                            <input type="text" class="form-control" required id="billing_city"
                              name='billing_city' value="{{$usergroup->address_city}}">
                          </div>
                          <div class="form-group col-sm-3">
                            <label for="billing_state">State</label>
                            <input type="text" class="form-control" required id="billing_state"
                              name='billing_state' value="{{$usergroup->address_state}}">
                          </div>

                          <div class="form-group col-sm-4">
                            <label for="billing_zip">Zip</label>
                            <input type="text" class="form-control" required id="billing_zip"
                              name='billing_zip' value="{{$usergroup->address_zip}}">
                          </div>

                          {{-- CARD INFO --}}
                          <div class="form-group col-sm-9">
                            <label for="billing_card_number">Card Number</label>
                            <input type="text" class="form-control" required
                              id="billing_card_number" name='billing_card_number'>
                          </div>
                          <div class="form-group col-sm-3">
                            <label for="billing_exp">Exp Date</label>
                            <input type="text" class="form-control" id="billing_exp"
                              name='billing_exp' placeholder="MM/YY" required>
                          </div>

                          {{-- <div class="form-group col-sm-3">
                            <label for="billing_cvv">CVV</label>
                            <input type="text" class="form-control" required id="billing_cvv" name='billing_cvv'>
                          </div> --}}

                          <div class="col-sm-12">
                            <input type='submit' class='btn btn-primary' value='Add Card'>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  {{-- BANK ACCOUNT --}}
                  <div id="credit_card_wrapper" class="card card-collapsed">
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
                        <input type="hidden" name='billing_profile_id' value="{{ $usergroup->authnet_id }}">
                        <input type="hidden" name='billing_usergroup_id' value="{{$usergroup->id}}">
                        <input type="hidden" name="billing_company" value="{{$usergroup->name}}">

                        {{-- BILLING INFO --}}
                        <div class="form-group col-sm-12">
                          <label for="name_on_account">
                            - If there is a need to attach a payment directly to the agent kindly do so in the agent profile page and not here. This method attaches the payment method to the agency itself.<br><br>
                            Name on Account
                          </label>
                          <input type="text" class="form-control form-control-sm" required
                            id="name_on_account" name='name_on_account'>
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
                          <div class="btn btn-sm btn-primary" onclick="get_bank_name()">Get Name from
                            Routing #</div>
                          <input type="text" class="form-control form-control-sm" id="bank_name"
                            name='bank_name'>
                        </div>


                        <div class="col-sm-12">
                          <input type='submit' class='btn btn-primary' value='Add Bank Account'>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<script>
  const group = @json($usergroup);
</script>
<script src="/modules/User/Resources/Admin/js/usergroups_profile.js"></script>
<script>
  // START GUIDED TOUR
  @if ($tour_check) window.guideChimp.start(); @endif

</script>
