<?php

namespace App\Http\Controllers\Admin\Rol;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class RolesController extends Controller
{
    use AuthorizesRequests;

    /**
     * Mostrar lista de roles.
     */
    public function index(Request $request)
    {
        if(!auth('api')->user()->can('list_rol')){
            return response()->json(["message" => "EL USUARIO NO ESTA AUTORIZADO"],403);
        }

        $name = $request->search;
    
        $roles = Role::where("name", "like", "%" . $name . "%")
                     ->orderBy("id", "desc")
                     ->get();
    
        return response()->json([
            "roles" => $roles->map(function($rol) {
                return [
                    "id" => $rol->id,
                    "name" => $rol->name,
                    "permissions" => $rol->permissions,
                    "permission_pluck" => $rol->permissions->pluck("name"), 
                    "created_at" => $rol->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);
    }
    
    /**
     * Mostrar un rol específico.
     */
    public function show($id)
    {
        if(!auth('api')->user()->can('edit_rol')){
            return response()->json(["message" => "EL USUARIO NO ESTA AUTORIZADO"],403);
        }
        try {
            $role = Role::findOrFail($id);
            return response()->json([
                "id" => $role->id,
                "name" => $role->name,
                "permissions" => $role->permissions,
                "permission_pluck" => $role->permissions->pluck("name"),
                "created_at" => $role->created_at->format("Y-m-d h:i:s")
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Rol no encontrado",
                "error" => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Crear un nuevo rol.
     */
    public function store(Request $request)
    {
        if(!auth('api')->user()->can('register_rol')){
            return response()->json(["message" => "EL USUARIO NO ESTA AUTORIZADO"],403);
        }
        // Validar la entrada
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array|min:1',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        // Verificar si el rol ya existe
        $is_role = Role::where("name", $request->name)->first();
        if ($is_role) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL ROL YA EXISTE"
            ], 403);
        }

        try {
            // Crear el nuevo rol
            $role = Role::create([
                'guard_name' => 'api',
                'name' => $request->name,
            ]);

            // Asignar permisos
            foreach ($request->permissions as $permission) {
                $role->givePermissionTo($permission);
            }

            return response()->json([
                "message" => 200,
                "message_text" => "Rol creado correctamente",
                "role" => $role
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                "message" => 500,
                "message_text" => "Error interno del servidor",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un rol existente.
     */
    public function update(Request $request, string $id)
{
    if(!auth('api')->user()->can('edit_rol')){
        return response()->json(["message" => "EL USUARIO NO ESTA AUTORIZADO"],403);
    }
    $request->validate([
        'name' => 'required|string|max:255|unique:roles,name,' . $id,
        'permissions' => 'array|min:1',
        'permissions.*' => 'string|exists:permissions,name'
    ]);

    try {
        $role = Role::findOrFail($id);
        
        // Actualiza solo el nombre
        $role->name = $request->name;
        $role->save();

        // Sincroniza los permisos
        $role->syncPermissions($request->permissions);

        return response()->json([
            "message" => "Rol actualizado correctamente",
            "role" => [
                "id" => $role->id,
                "name" => $role->name,
                "permissions" => $role->permissions->pluck("name")
            ]
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
        if(!auth('api')->user()->can('delete_rol')){
            return response()->json(["message" => "EL USUARIO NO ESTA AUTORIZADO"],403);
        }
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