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
            ->select('p.id','p.primer_nombre','p.primer_apellido','p.correo', DB::raw('GROUP_CONCAT(COALESCE(r.name, r.nombre_rol) SEPARATOR ",") as roles'))
            ->groupBy('p.id','p.primer_nombre','p.primer_apellido','p.correo')
            ->orderBy('p.id','desc')
            ->get();

        $roles = DB::table('roles')->select('id', DB::raw('COALESCE(name, nombre_rol) as name'))->orderBy('id')->get();

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
