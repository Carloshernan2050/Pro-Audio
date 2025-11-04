<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleAdminController extends Controller
{
    public function index()
    {
        $usuarios = DB::table('personas as p')
            ->leftJoin('personas_roles as pr', 'pr.personas_id', '=', 'p.id')
            ->leftJoin('roles as r', 'r.id', '=', 'pr.roles_id')
            ->select('p.id','p.primer_nombre','p.primer_apellido','p.correo', DB::raw('GROUP_CONCAT(DISTINCT COALESCE(r.name, r.nombre_rol) ORDER BY COALESCE(r.name, r.nombre_rol) SEPARATOR ",") as roles'))
            ->groupBy('p.id','p.primer_nombre','p.primer_apellido','p.correo')
            ->orderBy('p.id','desc')
            ->get()
            ->map(function($usuario) {
                // Normalizar roles: convertir "Usuario" a "Cliente" y eliminar duplicados
                if ($usuario->roles) {
                    $rolesArray = collect(explode(',', $usuario->roles))
                        ->map(function($rol) {
                            $rol = trim($rol);
                            return $rol === 'Usuario' ? 'Cliente' : $rol;
                        })
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                    $usuario->roles = implode(',', $rolesArray);
                }
                return $usuario;
            });

        // Obtener roles y agrupar por nombre normalizado
        $rolesRaw = DB::table('roles')
            ->select('id', DB::raw('COALESCE(name, nombre_rol) as name'))
            ->orderBy('id')
            ->get();
        
        $rolesAgrupados = [];
        foreach ($rolesRaw as $role) {
            $nombre = trim($role->name ?? '');
            // Normalizar: convertir "Usuario" a "Cliente"
            if ($nombre === 'Usuario') {
                $nombre = 'Cliente';
            }
            
            // Solo procesar roles permitidos
            if (in_array($nombre, ['Superadmin', 'Admin', 'Cliente'], true)) {
                // Si ya existe un rol con este nombre, usar el primero (menor ID)
                if (!isset($rolesAgrupados[$nombre]) || $role->id < $rolesAgrupados[$nombre]->id) {
                    $role->name = $nombre;
                    $rolesAgrupados[$nombre] = $role;
                }
            }
        }
        
        // Convertir a colección y ordenar
        $roles = collect($rolesAgrupados)
            ->sortBy(function($role) {
                $orden = ['Superadmin' => 1, 'Admin' => 2, 'Cliente' => 3];
                return $orden[$role->name] ?? 999;
            })
            ->values();

        return view('admin.roles', compact('usuarios','roles'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|integer|exists:personas,id',
            'roles' => 'array'
        ]);

        $personaId = (int)$request->persona_id;
        $roleIds = collect($request->roles ?? [])->map(fn($v)=>(int)$v)->filter()->values()->all();

        // No permitir asignar Superadmin a uno mismo a menos que ya lo sea (simple safeguard)
        // Se puede ampliar con políticas

        DB::transaction(function() use ($personaId, $roleIds) {
            DB::table('personas_roles')->where('personas_id', $personaId)->delete();
            foreach ($roleIds as $rid) {
                DB::table('personas_roles')->insert(['personas_id'=>$personaId,'roles_id'=>$rid]);
            }
        });

        return back()->with('success', 'Roles actualizados correctamente');
    }
}
