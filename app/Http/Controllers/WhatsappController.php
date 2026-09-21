<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\SolicitudReservaWhatsapp;
use App\Models\WhatsappConversacion;
use App\Models\WhatsappFlujoNodo;
use App\Models\WhatsappMensaje;
use App\Services\FechaLibreParser;
use App\Services\WhatsappBot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    public const NUMERO_PRUEBA = 'prueba-panel';

    public function index(WhatsappBot $bot)
    {
        $estado = $bot->estado();

        return view('whatsapp.index', compact('estado'));
    }

    public function estado(WhatsappBot $bot)
    {
        return response()->json($bot->estado());
    }

    public function conectar(WhatsappBot $bot)
    {
        $bot->conectar();

        return back();
    }

    public function desconectar(WhatsappBot $bot)
    {
        $bot->desconectar();

        return back()->with('status', 'WhatsApp desconectado.');
    }

    /**
     * El bot llama a esta ruta cada vez que llega un mensaje nuevo (texto o imagen).
     * Devuelve el texto de respuesta que el bot debe enviar.
     */
    public function webhook(Request $request, WhatsappBot $bot)
    {
        if ($request->header('X-Bot-Token') !== config('whatsapp.bot_token')) {
            abort(403);
        }

        $data = $request->validate([
            'numero'  => ['required', 'string'],
            'nombre'  => ['nullable', 'string'],
            'mensaje' => ['nullable', 'string'],
            'imagen'  => ['nullable', 'string'], // base64
        ]);

        $conversacion = WhatsappConversacion::firstOrCreate(['numero' => $data['numero']]);

        // Mientras un admin está atendiendo la conversación por Chat en vivo, el bot
        // automático no debe contestar nada (si no, el menú de bienvenida se metería
        // encima de cada mensaje que el cliente le escribe al admin) — solo se guarda
        // el mensaje para que aparezca en el chat, y el admin responde a mano.
        if ($conversacion->chat_manual) {
            WhatsappMensaje::create([
                'numero'  => $data['numero'],
                'origen'  => 'cliente',
                'nombre'  => $data['nombre'] ?? null,
                'mensaje' => $data['mensaje'] ?? '',
                'imagen'  => ($data['imagen'] ?? null) ? $this->guardarImagen($data['imagen']) : null,
            ]);

            return response()->json(['reply' => null, 'imagen' => null]);
        }

        $mensajes = $this->avanzarFlujo($conversacion, $data['mensaje'] ?? '', $data['imagen'] ?? null);
        $primero = $mensajes[0] ?? ['texto' => 'Listo.', 'imagen' => null];

        foreach (array_slice($mensajes, 1) as $extra) {
            $bot->enviar($conversacion->numero, $extra['texto'], $extra['imagen'] ? asset('storage/' . $extra['imagen']) : null);
        }

        WhatsappMensaje::create([
            'numero'    => $data['numero'],
            'nombre'    => $data['nombre'] ?? null,
            'mensaje'   => ($data['imagen'] ?? null) ? '[imagen]' : ($data['mensaje'] ?? ''),
            'respuesta' => $primero['texto'],
        ]);

        return response()->json([
            'reply'  => $primero['texto'],
            'imagen' => $primero['imagen'] ? asset('storage/' . $primero['imagen']) : null,
        ]);
    }

    /**
     * Simulador para el panel "Conexión de flujos": corre el mismo motor pero sin
     * mandar nada por WhatsApp real ni guardar clientes/solicitudes de verdad.
     */
    public function probar(Request $request)
    {
        $data = $request->validate([
            'mensaje' => ['nullable', 'string'],
        ]);

        $conversacion = WhatsappConversacion::firstOrCreate(['numero' => self::NUMERO_PRUEBA]);
        $mensajes = collect($this->avanzarFlujo($conversacion, $data['mensaje'] ?? '', null, modoPrueba: true))
            ->map(fn ($m) => ['texto' => $m['texto'], 'imagen' => $m['imagen'] ? asset('storage/' . $m['imagen']) : null])
            ->all();

        return response()->json(['mensajes' => $mensajes]);
    }

    public function reiniciarPrueba()
    {
        WhatsappConversacion::where('numero', self::NUMERO_PRUEBA)->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Motor del flujo: recorre el grafo de nodos (editable desde el panel "Conexión de flujos").
     * Devuelve todos los mensajes generados en la cadena, en orden. El primero es la
     * respuesta síncrona del webhook; el resto se manda aparte con WhatsappBot::enviar()
     * (o, en modo prueba, se devuelven todos juntos para mostrarlos en el simulador).
     */
    protected function avanzarFlujo(WhatsappConversacion $c, string $texto, ?string $imagenBase64, bool $modoPrueba = false): array
    {
        $textoNormalizado = mb_strtolower(trim($texto));

        if ($textoNormalizado === 'cancelar') {
            $c->update(['nodo_actual_id' => null]);

            return [['texto' => 'Listo, cancelé el proceso. Escribí *1* cuando quieras empezar de nuevo.', 'imagen' => null]];
        }

        $nodo = $c->nodoActual ?? $this->nodoInicial();
        // Si la conversación ya tenía un nodo guardado, el mensaje que llega es la
        // respuesta a lo último que preguntó el bot. Pero si es un número nuevo (o
        // reinició con "cancelar"), el PRIMER mensaje — sea cual sea, "hola", "1",
        // cualquier palabra — siempre tiene que mostrar el saludo y el menú primero;
        // recién el mensaje SIGUIENTE se interpreta como la elección del cliente.
        $reanudando = (bool) $c->nodoActual;
        $mensajes = [];
        $vueltas = 0;

        while ($nodo && $vueltas < 20) {
            $vueltas++;

            if ($reanudando && $this->esperaRespuesta($nodo->tipo)) {
                $resultado = $this->recibirRespuesta($nodo, $c, $texto, $imagenBase64, $modoPrueba);

                if (! $resultado['ok']) {
                    return [['texto' => $resultado['mensaje'], 'imagen' => $nodo->imagen]];
                }

                $nodo = $resultado['siguiente'];
                $reanudando = false;

                continue;
            }

            $emision = $this->emitirNodo($nodo, $c, $modoPrueba);

            if ($emision['mensaje']) {
                $mensajes[] = ['texto' => $emision['mensaje'], 'imagen' => $nodo->imagen];
            }

            if ($emision['espera']) {
                $c->update(['nodo_actual_id' => $nodo->id]);

                return $mensajes;
            }

            $nodo = $emision['siguiente'];
        }

        $c->update(['nodo_actual_id' => null]);

        return $mensajes ?: [['texto' => 'Listo. Escribí *1* para hacer una reserva.', 'imagen' => null]];
    }

    protected function nodoInicial(): ?WhatsappFlujoNodo
    {
        return WhatsappFlujoNodo::where('clave', 'menu_inicial')->first();
    }

    protected function esperaRespuesta(string $tipo): bool
    {
        return in_array($tipo, [
            WhatsappFlujoNodo::TIPO_MENU_INICIAL,
            WhatsappFlujoNodo::TIPO_PREGUNTA_TEXTO,
            WhatsappFlujoNodo::TIPO_PREGUNTA_FOTO,
            WhatsappFlujoNodo::TIPO_ELEGIR_LOCAL,
            WhatsappFlujoNodo::TIPO_ESPERAR_COMPROBANTE_PRENDA,
            WhatsappFlujoNodo::TIPO_ESPERAR_CUARTO_SUGERIDO,
        ], true);
    }

    /**
     * El cliente llega a este nodo por primera vez en la cadena: se manda su mensaje
     * y se decide si el flujo espera una respuesta del cliente o sigue de largo.
     */
    protected function emitirNodo(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, bool $modoPrueba): array
    {
        return match ($nodo->tipo) {
            WhatsappFlujoNodo::TIPO_GUARDAR_CLIENTE => $this->accionGuardarCliente($nodo, $c, $modoPrueba),
            WhatsappFlujoNodo::TIPO_CREAR_SOLICITUD => $this->accionCrearSolicitud($nodo, $c, $modoPrueba),
            WhatsappFlujoNodo::TIPO_MENSAJE_FINAL => ['mensaje' => $nodo->mensaje, 'espera' => false, 'siguiente' => $this->siguienteNodo($nodo)],
            default => ['mensaje' => $nodo->mensaje, 'espera' => true, 'siguiente' => null],
        };
    }

    /**
     * El cliente ya había recibido la pregunta de este nodo; ahora llega su respuesta.
     */
    protected function recibirRespuesta(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, string $texto, ?string $imagenBase64, bool $modoPrueba = false): array
    {
        return match ($nodo->tipo) {
            WhatsappFlujoNodo::TIPO_MENU_INICIAL => $this->recibirMenuInicial($nodo, $texto),
            WhatsappFlujoNodo::TIPO_PREGUNTA_TEXTO => $this->recibirPreguntaTexto($nodo, $c, $texto),
            WhatsappFlujoNodo::TIPO_PREGUNTA_FOTO => $this->recibirPreguntaFoto($nodo, $c, $imagenBase64, $texto, $modoPrueba),
            WhatsappFlujoNodo::TIPO_ELEGIR_LOCAL => $this->recibirElegirLocal($nodo, $c, $texto),
            WhatsappFlujoNodo::TIPO_ESPERAR_COMPROBANTE_PRENDA => $this->recibirComprobantePrenda($nodo, $c, $imagenBase64, $texto, $modoPrueba),
            WhatsappFlujoNodo::TIPO_ESPERAR_CUARTO_SUGERIDO => $this->recibirCuartoSugerido($nodo, $c, $texto),
            default => ['ok' => false, 'mensaje' => 'No entendí eso.'],
        };
    }

    protected function recibirMenuInicial(WhatsappFlujoNodo $nodo, string $texto): array
    {
        $normalizado = mb_strtolower(trim($texto));
        $siguiente = $nodo->conexionesSalida()->where('valor', $normalizado)->first();

        if (! $siguiente) {
            return ['ok' => false, 'mensaje' => $nodo->mensaje];
        }

        return ['ok' => true, 'siguiente' => $siguiente->destino];
    }

    protected function recibirPreguntaTexto(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, string $texto): array
    {
        if (trim($texto) === '') {
            return ['ok' => false, 'mensaje' => 'No recibí nada, ¿podés escribirlo de nuevo?'];
        }

        // La semana no se puede aceptar tal cual si ya pasó — se le avisa al
        // cliente en el momento en vez de dejar que la solicitud quede con una
        // fecha vieja que después no se puede anclar en el tablero de Reservas.
        if ($nodo->campo_destino === 'semana_texto') {
            $fecha = FechaLibreParser::parseSinCorregir($texto);

            if ($fecha && $fecha->startOfWeek(Carbon::MONDAY)->lt(now()->startOfWeek(Carbon::MONDAY))) {
                return ['ok' => false, 'mensaje' => 'Esa fecha ya pasó 😅. ¿Para qué semana querés hacer la reserva?'];
            }
        }

        if ($nodo->campo_destino) {
            $c->update([$nodo->campo_destino => trim($texto)]);
        }

        return ['ok' => true, 'siguiente' => $this->siguienteNodo($nodo)];
    }

    protected function recibirPreguntaFoto(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, ?string $imagenBase64, string $texto = '', bool $modoPrueba = false): array
    {
        // En el simulador no se pueden mandar fotos reales: cualquier texto no vacío
        // se acepta como si fuera la foto, para poder probar el resto del flujo.
        if ($modoPrueba && trim($texto) !== '') {
            if ($nodo->campo_destino) {
                $c->update([$nodo->campo_destino => '[foto de prueba]']);
            }

            return ['ok' => true, 'siguiente' => $this->siguienteNodo($nodo)];
        }

        if (! $imagenBase64) {
            return ['ok' => false, 'mensaje' => 'Necesito que me envíes una *foto* para continuar.'];
        }

        if ($nodo->campo_destino) {
            $c->update([$nodo->campo_destino => $this->guardarImagen($imagenBase64)]);
        }

        return ['ok' => true, 'siguiente' => $this->siguienteNodo($nodo)];
    }

    protected function recibirElegirLocal(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, string $texto): array
    {
        $normalizado = trim($texto);
        $local = match ($normalizado) {
            '1' => 'Casa Blanca',
            '2' => 'Casa Blanca VIP',
            default => null,
        };

        if (! $local) {
            return ['ok' => false, 'mensaje' => 'Esa opción no es válida. Respondé *1* (Casa Blanca) o *2* (Casa Blanca VIP).'];
        }

        $c->update(['local_texto' => $local]);

        return ['ok' => true, 'siguiente' => $this->siguienteNodo($nodo)];
    }

    /**
     * Este nodo solo se activa cuando un admin aprueba una solicitud desde el panel
     * (ver SolicitudReservaWhatsappController::aprobar) y deja la conversación
     * "parada" acá esperando la foto del comprobante de depósito de la prenda.
     */
    protected function recibirComprobantePrenda(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, ?string $imagenBase64, string $texto = '', bool $modoPrueba = false): array
    {
        if (! $imagenBase64 && ! ($modoPrueba && trim($texto) !== '')) {
            return ['ok' => false, 'mensaje' => 'Necesito que me envíes la *foto del comprobante de depósito* para continuar.'];
        }

        $rutaComprobante = $modoPrueba ? '[foto de prueba]' : $this->guardarImagen($imagenBase64);
        $c->update(['prenda_comprobante' => $rutaComprobante]);

        if (! $modoPrueba && $c->solicitud_pendiente_id) {
            $c->solicitudPendiente?->update([
                'prenda_comprobante' => $rutaComprobante,
                'estado' => SolicitudReservaWhatsapp::PRENDA_EN_REVISION,
            ]);
        }

        return ['ok' => true, 'siguiente' => $this->siguienteNodo($nodo)];
    }

    /**
     * Este nodo solo se activa cuando un admin le sugiere cuartos alternativos a una
     * solicitud pendiente (ver SolicitudReservaWhatsappController::sugerir) y deja la
     * conversación "parada" acá esperando que el cliente elija uno de los sugeridos.
     */
    protected function recibirCuartoSugerido(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, string $texto): array
    {
        $solicitud = $c->solicitudPendiente;
        $sugeridos = collect($solicitud?->cuartos_sugeridos ?? []);

        $normalizado = mb_strtolower(trim($texto));

        if ($normalizado === 'no') {
            $solicitud?->update(['cuartos_sugeridos' => null]);
            $c->update(['nodo_actual_id' => null, 'solicitud_pendiente_id' => null]);

            return ['ok' => false, 'mensaje' => 'Entendido, no hay problema. Cuando quieras hacer otra reserva escribí *1*.'];
        }

        // Cuando se sugiere un solo cuarto, el mensaje le pide "SI"/"NO" en vez del
        // número (no tiene sentido pedirle que "elija" entre una sola opción).
        if ($sugeridos->count() === 1 && in_array($normalizado, ['si', 'sí'], true)) {
            $elegido = $sugeridos->first();
        } else {
            $limpiar = fn (string $valor) => mb_strtolower(preg_replace('/[^0-9A-Za-z]/', '', $valor));
            $elegido = $sugeridos->first(fn ($s) => $limpiar((string) ($s['cuarto'] ?? '')) === $limpiar($texto));
        }

        if (! $elegido) {
            $mensaje = $sugeridos->count() === 1
                ? 'No entendí tu respuesta. Respondé *SI* para aceptar el cuarto sugerido o *NO* si no lo querés.'
                : 'No entendí cuál elegiste. Respondé con el número del cuarto que te sugerimos: ' . $sugeridos->map(fn ($s) => $s['cuarto'])->implode(', ') . '.';

            return ['ok' => false, 'mensaje' => $mensaje];
        }

        $solicitud->update([
            'local' => $elegido['local'],
            'cuarto' => $elegido['cuarto'],
            'cuartos_sugeridos' => null,
        ]);

        return ['ok' => true, 'siguiente' => $this->siguienteNodo($nodo)];
    }

    protected function siguienteNodo(WhatsappFlujoNodo $nodo): ?WhatsappFlujoNodo
    {
        return $nodo->conexionesSalida()->whereNull('valor')->first()?->destino;
    }

    // --- Nodos de acción del sistema (lógica fija, mensaje editable) ---

    protected function accionGuardarCliente(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, bool $modoPrueba): array
    {
        if (! $modoPrueba) {
            Cliente::updateOrCreate(
                ['cedula' => $c->cedula],
                [
                    'nombre_completo' => $c->nombre,
                    'foto_cedula_frontal' => $c->foto_lateral,
                    'foto_cedula_trasera' => $c->foto_posterior,
                ]
            );
        }

        return ['mensaje' => $nodo->mensaje, 'espera' => false, 'siguiente' => $this->siguienteNodo($nodo)];
    }

    /**
     * Registra la solicitud como PENDIENTE. No se confirma nada automáticamente:
     * un administrador la revisa a mano desde el panel de Solicitudes.
     */
    protected function accionCrearSolicitud(WhatsappFlujoNodo $nodo, WhatsappConversacion $c, bool $modoPrueba): array
    {
        if (! $modoPrueba) {
            SolicitudReservaWhatsapp::create([
                'numero' => $c->numero,
                'nombre' => $c->nombre,
                'cedula' => $c->cedula,
                'foto_lateral' => $c->foto_lateral,
                'foto_posterior' => $c->foto_posterior,
                'local' => $c->local_texto,
                'cuarto' => $c->cuarto_texto,
                'semana_texto' => $c->semana_texto,
                'estado' => SolicitudReservaWhatsapp::PENDIENTE,
            ]);
        }

        return ['mensaje' => $nodo->mensaje, 'espera' => false, 'siguiente' => $this->siguienteNodo($nodo)];
    }

    protected function guardarImagen(string $base64): string
    {
        $contenido = base64_decode($base64);
        $nombreArchivo = 'whatsapp-reservas/' . Str::uuid() . '.jpg';

        Storage::disk('public')->put($nombreArchivo, $contenido);

        return $nombreArchivo;
    }
}
