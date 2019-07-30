<?php

namespace App\Http\Controllers\Security;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;

class IndexController extends Controller
{
  /* funciones para crear roles y permisos */

  public function createRol()
  {
    try {
      $role = Role::create(['name' => 'gerencia']);
      return array(
        'code' => 200,
        'datos' => $role
      );
    } catch (Exception $e) {
      return array(
        'code' => 500,
        'error' => $e
      );
    }

  }

  public function createPermission()
  {
    try {
      $permission = Permission::create(['name' => 'index report']);
      return array(
        'code' => 200,
        'datos' => $permission
      );
    } catch (Exception $e) {
      return array(
        'code' => 500,
        'error' => $e
      );
    }
  }

  public function assignRoleToUser()
  {
    try {
      $user = User::findOrFail(1);
      $user->assignRole('gerencia');
      return array(
        'code' => 200,
        'datos' => $user
      );
    } catch (Exception $e) {
      return array(
        'code' => 500,
        'error' => $e
      );
    }
  }

  public function assignRoleToPermission()
  {
    try {
      // https://docs.spatie.be/laravel-permission/v2/basic-usage/basic-usage/
      // se pueden asignar multiples permisos a un rol.. ver documentacion
      $permission = Permission::findOrFail(1);
      $role = Role::findOrFail(1);
      $permission->assignRole($role);
      return array(
        'code' => 200,
        'permission' => $permission,
        'role' => $role
      );
    } catch (Exception $e) {
      return array(
        'code' => 500,
        'error' => $e
      );
    }
  }

  public function deleteRoleToPermission()
  {
    try {
      $permission = Permission::findOrFail(1);
      $role = Role::findOrFail(1);
      $permission->removeRole($role);
      return array(
        'code' => 200,
        'permission' => $permission,
        'role' => $role
      );
    } catch (Exception $e) {
      return array(
        'code' => 500,
        'error' => $e
      );
    }
  }
}
