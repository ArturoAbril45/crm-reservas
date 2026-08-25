<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RoleController extends Controller
{
    public const MODULOS = [
        'almacenes'      => 'Almacenes',
        'productos'      => 'Productos',
        'ventas'         => 'Venta',
        'traspasos'      => 'Traspasos',
        'compras'        => 'Compras',
        'gastos'         => 'Gastos',
        'caja'           => 'Caja',
        'sucursales.index' => 'Sucursales',
        'reporte-ventas' => 'Reporte ventas',
        'roles'          => 'Roles',
    ];

    public function index()
    {
        $usuarios = User::orderBy('name')->get();
        $modulos = self::MODULOS;

        return view('roles.index', compact('usuarios', 'modulos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'username'       => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8'],
            'role'           => ['required', 'string', 'max:255'],
            'permisos'       => ['array'],
            'permisos.*'     => ['string', 'in:' . implode(',', array_keys(self::MODULOS))],
            'horario_activo' => ['nullable', 'boolean'],
            'hora_inicio'    => ['required', 'date_format:H:i'],
            'hora_fin'       => ['required', 'date_format:H:i'],
            'dias_trabajo'   => ['array'],
            'dias_trabajo.*' => ['integer', 'between:0,6'],
        ]);

        $horarioActivo = $request->boolean('horario_activo');

        if ($horarioActivo && empty($data['dias_trabajo'])) {
            return back()->withErrors(['dias_trabajo' => 'Seleccioná al menos un día de trabajo.']);
        }

        User::create([
            'name'           => $data['name'],
            'username'       => $data['username'],
            'email'          => $data['email'],
            'password'       => Hash::make($data['password']),
            'role'           => $data['role'],
            'permisos'       => $data['permisos'] ?? [],
            'horario_activo' => $horarioActivo,
            'hora_inicio'    => $data['hora_inicio'],
            'hora_fin'       => $data['hora_fin'],
            'dias_trabajo'   => implode(',', $data['dias_trabajo'] ?? []),
        ]);

        return redirect()->route('roles')->with('status', 'Usuario creado correctamente.');
    }

    public function actualizarPermisos(Request $request, User $usuario)
    {
        $data = $request->validate([
            'permisos'    => ['array'],
            'permisos.*'  => ['string', 'in:' . implode(',', array_keys(self::MODULOS))],
        ]);

        $usuario->update(['permisos' => $data['permisos'] ?? []]);

        return redirect()->route('roles')->with('status', 'Permisos de ' . $usuario->name . ' actualizados correctamente.');
    }

    public function actualizarHorario(Request $request, User $usuario)
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['horario_activo' => 'No podés configurar un horario de acceso para tu propio usuario.']);
        }

        $data = $request->validate([
            'horario_activo' => ['nullable', 'boolean'],
            'hora_inicio'    => ['required', 'date_format:H:i'],
            'hora_fin'       => ['required', 'date_format:H:i'],
            'dias_trabajo'   => ['array'],
            'dias_trabajo.*' => ['integer', 'between:0,6'],
        ]);

        $horarioActivo = $request->boolean('horario_activo');

        if ($horarioActivo && empty($data['dias_trabajo'])) {
            return back()->withErrors(['dias_trabajo' => 'Seleccioná al menos un día de trabajo.']);
        }

        $usuario->update([
            'horario_activo' => $horarioActivo,
            'hora_inicio'    => $data['hora_inicio'],
            'hora_fin'       => $data['hora_fin'],
            'dias_trabajo'   => implode(',', $data['dias_trabajo'] ?? []),
        ]);

        return redirect()->route('roles')->with('status', 'Horario de ' . $usuario->name . ' actualizado correctamente.');
    }

    public function bloqueoHoy(Request $request, User $usuario)
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['bloqueado_fecha' => 'No podés bloquearte a vos mismo.']);
        }

        $usuario->update([
            'bloqueado_fecha' => $usuario->bloqueado_fecha?->isToday() ? null : now()->toDateString(),
        ]);

        return redirect()->route('roles')->with('status', $usuario->bloqueado_fecha
            ? $usuario->name . ' quedó bloqueado por hoy.'
            : 'Se quitó el bloqueo de hoy a ' . $usuario->name . '.');
    }
}
