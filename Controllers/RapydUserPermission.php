<?php
namespace Rapyd;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Rapyd\Model\PermissionCategories;
use DB;


class RapydUserPermission extends Controller
{
    public static function get_permissions()
    {
        $permissions = Permission::orderBy('id','DESC')->paginate(25);
        return $permissions;
    }

    public static function check_existing_permisison($perm_description) {
      $route = Permission::where('name', $perm_description)->first();
      return $route ? $route : false;
    }

    public function create(REQUEST $request)
    {
      $this->validate($request, [
          'permission_name' => 'required'
      ]);

      if (self::check_existing_permisison($request->permission_name)) {
        return back()->with('error', 'Permission label already exists');
      }

      $permission = Permission::create(['name' => \RapydCore::slugify($request->permission_name)]);

      if (!$request->new_category && (!isset($request->category) || $request->category === 'Uncategorized')) {
        $category = PermissionCategories::where('category_label', 'Uncategorized')->first();

        PermissionCategories::updateOrCreate(
          ['permission_id' => $permission->id],
          ['category_label' => 'Uncategorized']
        );
      } else {
        PermissionCategories::updateOrCreate(
          ['permission_id' => $permission->id],
          ['category_label' => $request->new_category ?? $request->category]
        );
      }

      return redirect('/admin/user/permissions/dashboard')->with('success', 'Permission successfully created');
    }

    public function update(Request $request)
    {
        $permission = Permission::find($request->permission_id);
        $permission->name = $request->permission_name;
        $permission->save();

        if (!$request->new_category && (!isset($request->category) || $request->category === 'Uncategorized')) {
          $category = PermissionCategories::where('category_label', 'Uncategorized')->first();

          PermissionCategories::updateOrCreate(
            ['permission_id' => $permission->id],
            ['category_label' => 'Uncategorized']
          );
        } else {
          PermissionCategories::updateOrCreate(
            ['permission_id' => $permission->id],
            ['category_label' => $request->new_category ?? $request->category]
          );
        }

        return redirect('/admin/user/permissions/dashboard')->with('success','Permission updated successfully');
    }

    public function delete($permission_id)
    {
        Permission::find($permission_id)->delete();
        return back()->with('success','Role successfully removed');
    }
}
