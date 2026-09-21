<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConversacion;
use App\Models\WhatsappMensaje;
use App\Services\WhatsappBot;
use Illuminate\Http\Request;

class WhatsappChatController extends Controller
{
    /**
     * Abre el chat en vivo con este número: pausa el bot automático (para que no
     * le siga contestando el menú de bienvenida encima de la conversación) y
     * devuelve el historial para pintar el modal la primera vez.
     */
    public function abrir(string $numero)
    {
        WhatsappConversacion::firstOrCreate(['numero' => $numero])->update(['chat_manual' => true]);

        return response()->json(['mensajes' => $this->hilo($numero)]);
    }

    /**
     * Polling del modal mientras está abierto: solo lectura, no cambia nada.
     */
    public function mensajes(string $numero)
    {
        return response()->json(['mensajes' => $this->hilo($numero)]);
    }

    public function enviar(Request $request, string $numero, WhatsappBot $bot)
    {
        $data = $request->validate([
            'mensaje' => ['required', 'string', 'max:1000'],
        ]);

        $resultado = $bot->enviar($numero, $data['mensaje']);

        if (! ($resultado['ok'] ?? false)) {
            return response()->json(['ok' => false, 'error' => $resultado['error'] ?? 'No se pudo enviar el mensaje.'], 422);
        }

        WhatsappMensaje::create([
            'numero'  => $numero,
            'origen'  => 'admin',
            'nombre'  => $request->user()->name,
            'mensaje' => $data['mensaje'],
        ]);

        return response()->json(['mensajes' => $this->hilo($numero)]);
    }

    /**
     * Le devuelve el número al bot automático (por ejemplo, cuando el admin ya
     * terminó de aclarar algo por chat y quiere que el flujo normal siga
     * funcionando para ese cliente).
     */
    public function reanudar(string $numero)
    {
        WhatsappConversacion::where('numero', $numero)->update(['chat_manual' => false]);

        return response()->json(['ok' => true]);
    }

    /**
     * Arma el hilo en orden cronológico mezclando: mensajes del cliente,
     * respuestas automáticas históricas del bot (columna "respuesta", de antes
     * de que existiera el chat en vivo) y mensajes que mandó el admin a mano.
     */
    private function hilo(string $numero): array
    {
        $filas = WhatsappMensaje::where('numero', $numero)->orderBy('created_at')->orderBy('id')->limit(200)->get();

        $hilo = [];

        foreach ($filas as $fila) {
            $hilo[] = [
                'origen'  => $fila->origen === 'admin' ? 'admin' : 'cliente',
                'texto'   => $fila->mensaje,
                'imagen'  => $fila->imagen ? asset('storage/' . $fila->imagen) : null,
                'hora'    => $fila->created_at->format('d/m H:i'),
            ];

            if ($fila->respuesta) {
                $hilo[] = [
                    'origen'  => 'bot',
                    'texto'   => $fila->respuesta,
                    'imagen'  => null,
                    'hora'    => $fila->created_at->format('d/m H:i'),
                ];
            }
        }

        return $hilo;
    }
}
