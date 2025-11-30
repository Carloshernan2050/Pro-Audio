<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleAdminController extends Controller
{
    public function index()
    {
        // Usar sintaxis compatible con SQLite (no soporta SEPARATOR)
        $connection = DB::getDriverName();
        if ($connection === 'sqlite') {
            $usuarios = DB::table('personas as p')
                ->leftJoin('personas_roles as pr', 'pr.personas_id', '=', 'p.id')
                ->leftJoin('roles as r', 'r.id', '=', 'pr.roles_id')
                ->select('p.id','p.primer_nombre','p.primer_apellido','p.correo', DB::raw('GROUP_CONCAT(DISTINCT COALESCE(r.name, r.nombre_rol)) as roles'))
                ->groupBy('p.id','p.primer_nombre','p.primer_apellido','p.correo')
                ->orderBy('p.id','desc')
                ->get();
        } else {
            $usuarios = DB::table('personas as p')
                ->leftJoin('personas_roles as pr', 'pr.personas_id', '=', 'p.id')
                ->leftJoin('roles as r', 'r.id', '=', 'pr.roles_id')
                ->select('p.id','p.primer_nombre','p.primer_apellido','p.correo', DB::raw('GROUP_CONCAT(DISTINCT COALESCE(r.name, r.nombre_rol) ORDER BY COALESCE(r.name, r.nombre_rol) SEPARATOR ",") as roles'))
                ->groupBy('p.id','p.primer_nombre','p.primer_apellido','p.correo')
                ->orderBy('p.id','desc')
                ->get();
        }
        
        $usuarios = $usuarios
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
            // Si ya existe un rol con este nombre, usar el primero (menor ID)
            if (in_array($nombre, ['Superadmin', 'Admin', 'Cliente'], true)
                && (!isset($rolesAgrupados[$nombre]) || $role->id < $rolesAgrupados[$nombre]->id)) {
                $role->name = $nombre;
                $rolesAgrupados[$nombre] = $role;
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
            'role_id' => 'nullable|integer|exists:roles,id'
        ]);

        $personaId = (int) $request->persona_id;
        $roleId = $request->filled('role_id') ? (int) $request->role_id : null;

        $rolesPermitidos = DB::table('roles')
            ->select('id', DB::raw('COALESCE(name, nombre_rol) as name'))
            ->get()
            ->filter(function ($role) {
                $nombre = trim($role->name ?? '');
                if ($nombre === 'Usuario') {
                    $nombre = 'Cliente';
                }
                return in_array($nombre, ['Superadmin', 'Admin', 'Cliente'], true);
            })
            ->pluck('name', 'id');

        if (!is_null($roleId) && !$rolesPermitidos->has($roleId)) {
            return back()->withErrors(['role_id' => 'El rol seleccionado no está permitido.']);
        }

        DB::transaction(function () use ($personaId, $roleId) {
            DB::table('personas_roles')->where('personas_id', $personaId)->delete();

            if (!is_null($roleId)) {
                DB::table('personas_roles')->insert([
                    'personas_id' => $personaId,
                    'roles_id' => $roleId
                ]);
            }
        });

        return back()->with('success', 'Roles actualizados correctamente');
    }
}

