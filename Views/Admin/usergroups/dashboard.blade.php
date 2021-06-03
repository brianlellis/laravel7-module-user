@php
  $data   = \ui_AdminDashboard::dashboard_method('usergroup');
  $types  = Rapyd\Model\UsergroupType::orderBy('description')->get();
@endphp

@can('user-view')
  @dashboard_table_header('
    Usergroup,
    /admin/usergroups/create,
    users
  ')

  <div class="row" style="margin-top: -20px">
    <div class="col-3">
      <form action="/admin/usergroups/dashboard">
          <select name="type" class="form-control" onchange="this.form.submit()">
            <option value>Filter Usergroup Type</option>
            @foreach ($types as $type)
              <option 
                value="{{$type->id}}" 
                @if($type->id == request('type')) selected @endif
              >
                {{ucfirst($type->description)}}
              </option>
            @endforeach
          </select>
      </form>
    </div>
    <div class="col-8">
      <a href="/admin/usergroups/dashboard" class="btn btn-primary">Clear Filters</a>
    </div>
  </div>

  @dashboard_table('ID #, Name, Type, Address, Action,'{!! $data->paginate !!})
    @foreach ($data->usergroups as $key => $usergroup)
      @if(isset($usergroup->id))
        <tr>
          <td>{{ $usergroup->id }}</td>

          @if ($usergroup->name)
            <td>{{$usergroup->name}}</td>
          @else
            <td>Name Empty</td>
          @endif

          @if($usergroup->type)
            <td>{{ ucfirst($usergroup->type->description) }}</td>
          @else
            <td>No Type</td>
          @endif

          <td>{{ $usergroup->address() }}</td>

          <td class="d-flex">
            @can('user-update')
              <div class="mr-2">
                <a
                  class="btn btn-sm btn-primary font-weight-bold"
                  href="/admin/usergroups/profile?group={{$usergroup->id}}"
                >
                  View
                </a>
              </div>
              <form class="form" action="{{ route('rapyd.usergroup.delete', $usergroup->id ) }}" method="POST"
                onsubmit="return confirm('Are you sure you want to delete {{ $usergroup->name }}')">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger font-weight-bold">Delete</button>
              </form>
            @endcan
          </td>
        </tr>
      @endif
    @endforeach
  @end_dashboard_table({!! $data->paginate !!})
@endcan
