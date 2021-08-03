@php
  use Spatie\Permission\Models\Permission;
  use Spatie\Permission\Models\Role;
  use Rapyd\Model\PermissionCategories;

  $categories       = PermissionCategories::groupBy('category_label')->get();
  $all_perm_cats    = PermissionCategories::get();
  $role             = Role::find(request()->input('role_id'));
  $role_permissions = $role->getAllPermissions();
  $all_permissions  = Permission::get();
  // ASSIGN ROLE TEMP
  // Auth::user()->assignRole($role->name);
@endphp

@can('sys-user-role-update')
  <form method="POST" action="{{route('rapyd.user.role.update')}}">
    @csrf
    <input type="hidden" name="role_id" value="{{$role->id}}" />

    <label>Role Name</label>
    <input type="text" style="margin-bottom: 20px" class="form-control" name="role_name" value="{{$role->name}}" />

    <label>Sign In Redirect</label>
    <input type="text" style="margin-bottom: 20px" class="form-control" name="signin_redirect" value="{{$role->signin_redirect}}" />

    {{-- TODO: NEED TO CHUNK PERMSSION FOR ROWS OF FOUR --}}
    <h2>Permissions</h2>
    @foreach ($categories as $category)
      <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{$category->category_label}}</h3>
            <div class="card-options">
              <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i class="fe fe-chevron-down"></i></a>
            </div>
        </div>

        <div class="card-body">
          <div class="row">
            @foreach($all_perm_cats as $all_perm_value)
              @if($all_perm_value->category_label === $category->category_label)
                @if($value = $all_permissions->find($all_perm_value->permission_id))
                  <div class="form-group col-sm-4">
                    <div class="material-switch">
                      <span style="margin-right: 10px;">{{$value->name}}</span>
                      <input id="{{$value->name}}" name="permission[]" value="{{$value->name}}" type="checkbox" @if($role_permissions->contains($value)) checked @endif>
              				<label for="{{$value->name}}" class="label-success"></label>
                    </div>
                  </div>
                @endif
              @endif
            @endforeach
          </div>
        </div>
      </div>
    @endforeach

    <button type="submit" class="btn btn-primary">Update Role</button>
  </form>
@endcan
