@php
  use Rapyd\Model\Usergroups;
  $all_user_roles = \Spatie\Permission\Models\Role::all();
  $group = null;

  if(request('group')) {
    $group = Usergroups::findOrFail(request('group'));
  }

  $group_types = \DB::table('usergroup_types')->get();
@endphp

@can('user-create')
  <form method="POST"
    action="{{ $group ? route('rapyd.usergroup.update', $group->id) : route('rapyd.usergroup.create') }}"
    enctype="multipart/form-data" class="row">
    @csrf
    <div class="col-sm-12">
      <div class="card">
        {{-- BASIC INFO --}}
        <div class="card-header d-flex justify-content-between">
          <h3 class="card-title">{{ request('group') ? 'Edit Group' : 'Create Group' }}</h3>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card-body">
              <div class="row">
                <div class="col-3">
                  <div class="form-group">
                    <label for="Avatar">Logo</label>
                    @if (isset($group) && $group->avatar)
                      <img src="{{ asset($group->avatar) }}" accept=".jpg,.jpeg,.png"
                        style="width: 100%; height: auto; display: block;">
                    @endif
                    <input type="file" accept=".jpg,.jpeg,.png" class="form-control" name="avatar" accept>
                  </div>
                </div>
                <div class="col-9">
                  <div class="form-group">
                    <label for="exampleInputnumber">Usergroup Type</label>
                    <select class="form-control" name="usergroup_type_id">
                      @foreach($group_types as $type)
                        <option value={{$type->id}}>{{$type->description}}</option>
                      @endforeach
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label for="exampleInputname">Name</label>
                    <input type="text" class="form-control" name="name" placeholder="Name">
                  </div>
                  <div class="form-group">
                    <label for="exampleInputnumber">Phone Number</label>
                    <input type="text" class="form-control phone" name="phone_main" placeholder="(   )   -">
                  </div>
              </div>
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
          <div class="col-md-8 col-sm-12">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <label for="exampleInputname">Street</label>
                  <input type="text" class="form-control" name="address_street" id="address_street" placeholder="Street">
                </div>
              </div>

              <div class="col-sm-12">
                <div class="form-group">
                  <label for="exampleInputname">Street 2</label>
                  <input type="text" class="form-control" name="address_street_2" id="address_street_2" placeholder="Street 2">
                </div>
              </div>

              <div class="col-sm-12 col-md-4">
                <div class="form-group">
                  <label for="exampleInputname">City</label>
                  <input type="text" class="form-control" name="address_city" id="address_city" placeholder="City">
                </div>
              </div>

              <div class="col-sm-12 col-md-4">
                <div class="form-group">
                  <label for="exampleInputname">State</label>
                  <input type="text" class="form-control" name="address_state" id="address_state" placeholder="State">
                </div>
              </div>

              <div class="col-sm-12 col-md-4">
                <div class="form-group">
                  <label for="exampleInputname">Zipcode</label>
                  <input type="text" class="form-control" name="address_zip" id="address_zip" placeholder="Zip">
                </div>
              </div>

              <div class="col-12">
                <div class="form-group">
                  <label for="exampleInputname">County</label>
                  <input 
                    type="text" class="form-control" name="address_county" 
                    id="address_county" placeholder="County"
                    @if($group->address_county) value="{{ $group->address_county}}" @endif
                  >
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div id="sl_map" style="width: 100%; height: 100%; min-height: 250px;"></div>
          </div>
        </div>
      </div>

      {{-- ADDITIONAL INFO --}}
      <div class="card-header">
        <h3 class="card-title">Additional Info</h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 col-sm-12">
            <div class="form-group">
              <label class="form-label">Renewal Billing</label>
              @php
              $allow_renewal = ['Direct', 'Agent']
              @endphp
              <select name="allow_renewal" id="" class="form-control">
                <option value="">Renewal Billing Type ( Direct / Agent )</option>
                @foreach ($allow_renewal as $item)
                  <option value="{{ $item }}" @if ($group && $group->allow_renewal === $item) selected
                @endif>{{ $item }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-9 col-sm-12">
            <div class="form-group">
              <label class="form-label">Referral Code</label>
              <input name="referral_code" class="form-control" placeholder="Referral Code">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-12">
            <div class="form-group">
              @php
              $entity_type = ['Corporation', 'Sole Proprietor', 'Partnership', 'LLC - Single Member', 'LLC - C
              Corporation', 'LLC - S Corporation', 'LLC - Partnership']
              @endphp
              <label class="form-label">Entity Type</label>
              <select name="entity_type" class="form-control">
                <option value="" class="placeholder">Entity Type</option>
                @foreach ($entity_type as $item)
                  <option value="{{ $item }}" @if ($group && $group->entity_type === $item) selected
                @endif>{{ $item }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-9 col-sm-12">
            <div class="form-group">
              <label class="form-label">License Number</label>
              <input name="license" class="form-control" placeholder="License Number">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-12">
            <div class="form-group">
              @php
              $tax_id_type = [
                [
                'label' => 'EIN: (xx-xxxxxxx)',
                'value' => 'EIN'
                ],
                [
                'label' => 'SSN: (xxx-xx-xxxx)',
                'value' => 'SSN'
                ],
              ]
              @endphp
              <label class="form-label">Tax ID Format</label>
              <select name="tax_id_type" class="form-control">
                <option value="">Format Type ( ENI / SSN )</option>
                @foreach ($tax_id_type as $item)
                  <option value="{{ $item['value'] }}" @if ($group && $group->tax_id_type === $item['value']) selected
                @endif>{{ $item['label'] }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-9 col-sm-12">
            <div class="form-group">
              <label class="form-label">Tax ID</label>
              <input name="tax_id" class="form-control" placeholder="Tax ID">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 col-sm-12">
            <div class="form-group">
              <label class="form-label">About</label>
              <textarea name="bio" class="form-control" rows="6"
                placeholder="Bio........."></textarea>
            </div>
          </div>

          <div class="col-md-6 col-sm-12">
            <div class="form-group">
              <label class="form-label">Website</label>
              <input name="social_website" class="form-control" placeholder="http://splink.com">
            </div>
            <div class="form-group">
              <label class="form-label">Twitter Profile</label>
              <input name="social_twitter" class="form-control" placeholder="http://splink.com">
            </div>
            <div class="form-group">
              <label class="form-label">Facebook Page</label>
              <input name="social_facebook" class="form-control" placeholder="http://splink.com">
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer">
        <a href="/admin/user/dashboard" class="btn btn-danger mt-1">Cancel</a>
        <button type="submit" class="btn btn-success mt-1">Save</button>
      </div>
    </div>
  </div>
</form>

<script src="/admin_resources/admin/js/usergroups_create.js"></script>
@endcan
