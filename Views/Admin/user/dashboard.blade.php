@php
  $data = \ui_AdminDashboard::dashboard_method('user');
  $roles = \m_Roles::orderBy('name')->get();
@endphp

@can('user-view')
  @dashboard_table_header('
    User,
    /admin/user/create,
    users
  ')
  <div class="row" style="margin-top: -20px">
      <div class="col-2">
      <form action="/admin/user/dashboard">
        <select name="role" class="form-control" onchange="this.form.submit()">
          <option value>Filter By Role</option>
          @foreach ($roles as $role)
            <option 
              value="{{$role->id}}" 
              @if($role->id == request('role')) selected @endif
            >
              {{ucwords($role->name)}}
            </option>
          @endforeach
        </select>
      </form>
    </div>
    <div class="col-8">
      <a href="/admin/user/dashboard" class="btn btn-primary">Clear Filters</a>
    </div>
  </div>

  @dashboard_table('ID #, Full Name , Email, Roles,Action,'{!! $data->paginate !!})
    @foreach ($data->users as $key => $user)
      @if(isset($user->id))
        <tr>
          <td>{{ $user->id }}</td>

          @if ($user->name_last && $user->name_first)
            <td>{{ $user->name_last }}, {{ $user->name_first }}</td>
          @else
            <td>Name Empty</td>
          @endif

          <td>{{ $user->email }}</td>
          <td>
            @if(!empty($user->getRoleNames()))
              @foreach($user->getRoleNames() as $v)
                <label class="badge badge-success">{{ $v }}</label>
              @endforeach
            @endif
          </td>
          <td class="d-flex">
            @can('user-update')
              <a class="btn btn-sm btn-primary mr-2 font-weight-bold" href="/admin/user/profile?user_id={{$user->id}}">Edit</a>
              <form action="/api/user/delete/{{$user->id}}" onsubmit="return confirm('Are you sure  you want to delete {{$user->email}}?');">
                <input class="btn btn-sm btn-danger font-weight-bold" type="submit" value="Remove"/>
              </form>
            @endcan
          </td>
        </tr>
      @endif
    @endforeach
  @end_dashboard_table({!! $data->paginate !!})
@endcan
