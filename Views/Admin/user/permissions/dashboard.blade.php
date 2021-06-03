@php
    use Rapyd\RapydUserPermission;
    use Rapyd\Model\PermissionCategories;
    $data       = RapydUserPermission::get_permissions();
    $categories = PermissionCategories::groupBy('category_label')->get();
    // HACK: Assigning role to user
    // Auth::user()->assignRole('Developer');
@endphp

@dashboard_table_header('Permissions')
@can('sys-user-permission-create')
  <form method="POST" action="{{route('rapyd.user.permission.create')}}">
    @csrf
    <div class="row">
      <div class="col-sm-4">
        <input type="text" name="permission_name" placheolder="Name" class="form-control" />
      </div>
      <div class="col-sm-3">
        <select name="category" class="form-control">
          <option value="">Select Category</option>
          @foreach ($categories as $category)
            <option value="{{$category->category_label}}">
              {{$category->category_label}}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-sm-3">
        <input name="new_category" class="form-control" placeholder="New Category">
      </div>
      <div class="col-sm-2">
        <button type="submit" class="btn btn-primary">Create Permssion</button>
      </div>
    </div>
  </form>
@endcan

@can('sys-user-permission-view')
  @dashboard_table('ID #, Name , Category, Action,'{!! $data->render() !!})
    @foreach ($data as $permission)
      <tr>
        <td>{{$permission->id}}</td>
        <td>{{$permission->name}}</td>
        @php
          $perm_cat = PermissionCategories::where('permission_id', $permission->id)->first();
        @endphp
        <td>
          {{$perm_cat->category_label}}
        </td>
        <td>
          @can('sys-user-permission-update')
            <a class="btn btn-sm btn-primary font-weight-bold" href="/admin/user/permissions/edit?permission_id={{$permission->id}}">Edit</a>
          @endcan
          @can('sys-user-permission-delete')
            <a class="btn btn-sm btn-danger font-weight-bold" href="/api/user/permissions/delete/{{$permission->id}}">Remove</a>
          @endcan
        </td>
      </tr>
    @endforeach
  @end_dashboard_table
@endcan
