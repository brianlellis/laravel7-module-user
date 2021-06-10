@php
    use Rapyd\RapydUserRole;
    $data = RapydUserRole::get_roles();
@endphp

@dashboard_table_header('Roles')

@can('sys-user-role-create')
  <form method="POST" action="{{route('rapyd.user.role.create')}}">
    @csrf
    <div class="row">
      <div class="col-sm-10">
        <input type="text" name="role_name" placeholder="Role Name" class="form-control" />
      </div>
      <div class="col-sm-2">
        <button type="submit" class="btn btn-primary">Create Role</button>
      </div>
    </div>
  </form>
@endcan

@dashboard_table('ID #, Name , Action,'{!! $data->render() !!}, 'hide_sort')
  @foreach ($data as $role)
    <tr>
      <td>{{$role->id}}</td>
      <td>{{$role->name}}</td>
      <td class="text-right">
        @can('sys-user-role-update')
          <a class="btn btn-primary btn-sm font-weight-bold" href="@url('/admin/user/roles/edit?role_id='){{$role->id}}">Edit</a>
        @endcan
        @can('sys-user-role-delete')
          <a class="btn btn-danger btn-sm font-weight-bold" href="@url('/api/user/role/delete/'){{$role->id}}">Remove</a>
        @endcan
      </td>
    </tr>
  @endforeach
@end_dashboard_table
