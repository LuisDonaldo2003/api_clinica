<?php

namespace App\Http\Controllers\Admin\Rol;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesController extends Controller
{
    /**
     * Mostrar lista de roles.
     */
    public function index(Request $request)
    {
        $name = $request->search;

        $roles = Role::where("name", "like", "%".$name."%")->orderBy("id","desc")->get();

        return response()->json([
            "roles" => $roles->map(function($rol) {
                return [
                    "id" => $rol->id,
                    "name" => $rol->name,
                    "permissions" => $rol->permissions->pluck('name'),
                    "created_at" => $rol->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);
    }

    /**
     * Crear un nuevo rol.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array|min:1',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        try {
            $role = Role::create([
                'guard_name' => 'api',
                'name' => $request->name,
            ]);

            foreach($request->permissions as $permission){
                $role->givePermissionTo($permission);
            }

            return response()->json([
                "message" => "Rol creado correctamente",
                "role" => $role
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                "message" => "Error interno del servidor",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un rol existente.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'array|min:1',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        try {
            $role = Role::findOrFail($id);

            $role->update(['name' => $request->name]);
            $role->syncPermissions($request->permissions);

            return response()->json([
                "message" => "Rol actualizado correctamente",
                "role" => $role
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "message" => "Error al actualizar el rol",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un rol.
     */
    public function destroy(string $id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();
            return response()->json([
                "message" => "Rol eliminado correctamente"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "message" => "Error al eliminar el rol",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}
