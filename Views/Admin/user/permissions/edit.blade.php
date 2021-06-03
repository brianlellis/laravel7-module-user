@php
    use Spatie\Permission\Models\Permission;
    use Rapyd\Model\PermissionCategories;
    $permission = Permission::find(request()->input('permission_id'));
    $categories = PermissionCategories::groupBy('category_label')->get();
    $perm_cat   = PermissionCategories::where('permission_id', $permission->id)->first();
@endphp

@can('sys-user-permission-update')
  @dashboard_table_header('Permissions')

  <form method="POST" action="{{route('rapyd.user.permission.update')}}">
    @csrf
    <input type="hidden" name="permission_id" value="{{$permission->id}}" />
    <div class="row">
      <div class="col-sm-4">
        <input type="text" name="permission_name" value="{{$permission->name}}" class="form-control" />
      </div>
      <div class="col-sm-3">
        <select name="category" class="form-control">
          <option value="">Select Category</option>
          @foreach ($categories as $category)
            <option value="{{$category->category_label}}" @if(isset($perm_cat->category_label) && $perm_cat->category_label === $category->category_label) selected @endif>
              {{$category->category_label}}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-sm-3">
        <input name="new_category" class="form-control" placeholder="New Category">
      </div>
      <div class="col-sm-2">
        <button type="submit" class="btn btn-primary">Update Permssion</button>
      </div>
    </div>
  </form>
@endcan
