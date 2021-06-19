<?php
namespace Rapyd;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;


class RapydUserRole extends Controller
{
    public static function get_roles()
    {
        $roles = Role::orderBy('id','DESC')->paginate(25);
        return $roles;
    }

    public function create(REQUEST $request)
    {
      $this->validate($request, [
          'role_name' => 'required'
      ]);

      Role::create(['name' => $request->role_name]);

      return redirect(request()->getSchemeAndHttpHost().'/admin/user/roles/dashboard')->with('success', 'User Role successfully created');
    }

    public function update(Request $request)
    {
        $role                   = Role::find($request->role_id);
        $role->name             = $request->role_name;
        $role->signin_redirect  = $request->signin_redirect;
        $role->save();

        if (isset($request->all()['permission'])) {
          $role->syncPermissions($request->permission);
        }

        return back()->with('success','Role updated successfully');
    }

    public function destroy($id)
    {
        Role::destroy($id);
        return back()->with('success','Role successfully removed');
    }
}
