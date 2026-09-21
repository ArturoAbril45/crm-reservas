<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConversacion;
use App\Models\WhatsappFlujoConexion;
use App\Models\WhatsappFlujoNodo;
use Database\Seeders\WhatsappFlujoSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsappFlujoController extends Controller
{
    public function index()
    {
        $nodos = WhatsappFlujoNodo::orderBy('id')->get();
        $conexiones = WhatsappFlujoConexion::all();
        $tipos = WhatsappFlujoNodo::tiposDisponibles();

        $camposDisponibles = [
            'nombre' => 'Nombre',
            'cedula' => 'Cédula',
            'foto_lateral' => 'Foto de cédula',
            'semana_texto' => 'Semana elegida',
            'cuarto_texto' => 'Cuarto solicitado',
        ];

        $conversacionesActivas = WhatsappConversacion::whereNotNull('nodo_actual_id')
            ->where('numero', '!=', \App\Http\Controllers\WhatsappController::NUMERO_PRUEBA)
            ->count();

        return view('whatsapp.flujos', compact('nodos', 'conexiones', 'tipos', 'camposDisponibles', 'conversacionesActivas'));
    }

    public function restaurar()
    {
        DB::transaction(function () {
            WhatsappFlujoConexion::query()->delete();
            WhatsappFlujoNodo::query()->delete();
            (new WhatsappFlujoSeeder())->run();
        });

        return response()->json(['ok' => true]);
    }

    public function moverNodo(Request $request, WhatsappFlujoNodo $nodo)
    {
        $data = $request->validate([
            'pos_x' => ['required', 'integer'],
            'pos_y' => ['required', 'integer'],
        ]);

        $nodo->update($data);

        return response()->json(['ok' => true]);
    }

    public function actualizarNodo(Request $request, WhatsappFlujoNodo $nodo)
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'mensaje' => ['nullable', 'string'],
            'campo_destino' => ['nullable', 'string', 'max:255'],
        ]);

        $nodo->update($data);

        return response()->json(['ok' => true]);
    }

    public function subirImagenNodo(Request $request, WhatsappFlujoNodo $nodo)
    {
        $data = $request->validate([
            'imagen' => ['required', 'image', 'max:4096'],
        ]);

        if ($nodo->imagen) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($nodo->imagen);
        }

        $ruta = $request->file('imagen')->store('whatsapp-flujo', 'public');
        $nodo->update(['imagen' => $ruta]);

        return response()->json(['ok' => true, 'imagen_url' => asset('storage/' . $ruta)]);
    }

    public function eliminarImagenNodo(WhatsappFlujoNodo $nodo)
    {
        if ($nodo->imagen) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($nodo->imagen);
            $nodo->update(['imagen' => null]);
        }

        return response()->json(['ok' => true]);
    }

    public function crearNodo(Request $request)
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:' . implode(',', array_keys(WhatsappFlujoNodo::tiposDisponibles()))],
            'titulo' => ['required', 'string', 'max:255'],
            'pos_x' => ['required', 'integer'],
            'pos_y' => ['required', 'integer'],
        ]);

        $clave = Str::slug($data['titulo'], '_') . '_' . Str::random(4);

        $nodo = WhatsappFlujoNodo::create([
            'clave' => $clave,
            'tipo' => $data['tipo'],
            'titulo' => $data['titulo'],
            'mensaje' => $data['tipo'] === WhatsappFlujoNodo::TIPO_MENSAJE_FINAL ? 'Escribí tu mensaje acá...' : '¿Qué querés preguntar?',
            'pos_x' => $data['pos_x'],
            'pos_y' => $data['pos_y'],
            'editable' => true,
            'eliminable' => true,
        ]);

        return response()->json(['ok' => true, 'nodo' => $nodo]);
    }

    public function eliminarNodo(WhatsappFlujoNodo $nodo)
    {
        // Los pasos "de sistema" (eliminable=false) están protegidos porque un cliente
        // real puede estar parado ahí a mitad de una conversación. La única excepción:
        // si el cuadro ya quedó completamente desconectado (sin ninguna flecha de
        // entrada ni de salida), no hay forma de que una conversación activa dependa de
        // él, así que es seguro dejarlo borrar igual aunque tenga la protección puesta.
        $estaAislado = ! WhatsappFlujoConexion::where('nodo_origen_id', $nodo->id)
            ->orWhere('nodo_destino_id', $nodo->id)
            ->exists();

        if (! $nodo->eliminable && ! $estaAislado) {
            return response()->json(['ok' => false, 'error' => 'Este paso es parte del sistema y no se puede eliminar.'], 422);
        }

        $nodo->delete();

        return response()->json(['ok' => true]);
    }

    public function crearConexion(Request $request)
    {
        $data = $request->validate([
            'nodo_origen_id' => ['required', 'exists:whatsapp_flujo_nodos,id'],
            'nodo_destino_id' => ['required', 'exists:whatsapp_flujo_nodos,id'],
            'valor' => ['nullable', 'string', 'max:255'],
            'etiqueta' => ['nullable', 'string', 'max:255'],
        ]);

        $conexion = WhatsappFlujoConexion::create($data);

        return response()->json(['ok' => true, 'conexion' => $conexion]);
    }

    public function eliminarConexion(WhatsappFlujoConexion $conexion)
    {
        $conexion->delete();

        return response()->json(['ok' => true]);
    }
}
