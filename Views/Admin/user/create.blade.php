@php
$all_user_roles = \Spatie\Permission\Models\Role::all();
@endphp

@can('user-create')
  <form method="POST" action="{{ route('rapyd.user.create') }}" class="row">
    @csrf
    <div class="col-sm-12">
      <div class="card">
        {{-- BASIC INFO --}}
        <div class="card-header">
          <h3 class="card-title">Create User</h3>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-sm-12 col-md-7">
              <div class="row">
                  <div class="col-12">
                    {{-- USER ROLE ASSIGNMENT --}}
                    <div class="form-group">
                      <label>User Role</labeL>
                      <select name="role_name" class="form-control" required>
                        <option value="">
                          Select a Role
                        </option>
                        @foreach ($all_user_roles as $role)
                          <option value="{{ $role->name }}">
                            {{ $role->name }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <div class="col-lg-6 col-md-12">
                    <div class="form-group">
                      <label for="exampleInputname">First Name</label>
                      <input type="text" class="form-control" name="name_first" placeholder="First Name" required>
                    </div>
                  </div>
                  <div class="col-lg-6 col-md-12">
                    <div class="form-group">
                      <label for="exampleInputname1">Last Name</label>
                      <input type="text" class="form-control" name="name_last" placeholder="Enter Last Name" required>
                    </div>
                  </div>
              </div>
              <div class="row">
                  <div class="col-lg-6 col-md-12">
                    <div class="form-group">
                      <label for="exampleInputnumber">Phone Number</label>
                      <input type="text" class="form-control phone" name="phone_main" placeholder="(     )   -">
                    </div>
                  </div>
                  <div class="col-lg-6 col-md-12">
                    <div class="form-group">
                      <label for="exampleInputEmail1">Email address</label>
                      <input type="email" class="form-control" name="email" placeholder="email address" required>
                    </div>
                  </div>
              </div>
            </div>
            <div class="col-sm-12 col-md-5">
              {{-- PASSWORD --}}
                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label>New Password</label>
                      <input type="password" name="password" class="form-control" placeholder="********" required>
                      @error('password')
                          <small class="text-danger">Password is required and must match confirm password.</small>
                      @enderror
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group">
                      <label>Confirm Password</label>
                      <input type="password" name="password_confirm" class="form-control" placeholder="********" required>
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
            <div class="col-sm-6">
              <div class="form-group">
                <label for="exampleInputname">Street</label>
                <input type="text" class="form-control" name="address_street" id="address_street" placeholder="Street">
              </div>
            </div>

            <div class="col-sm-6">
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
                <input type="text" class="form-control" name="address_county" id="address_county" placeholder="County">
              </div>
            </div>
            <div class="col-12">
              <div id="address_map"></div>
            </div>
          </div>
        </div>

        {{-- ADDITIONAL INFO --}}
        <div class="card-header">
          <h3 class="card-title">Additional Info</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 col-sm-12">
              <div class="form-group">
                <label class="form-label">About Me</label>
                <textarea name="bio" class="form-control" rows="6" placeholder="My bio........."></textarea>
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
  <script src="/admin_resources/admin/js/user_create.js"></script>
@endcan
