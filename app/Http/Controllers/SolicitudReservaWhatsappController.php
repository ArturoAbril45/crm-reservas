<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use App\Models\Prenda;
use App\Models\Reserva;
use App\Models\SolicitudReservaWhatsapp;
use App\Models\Sucursal;
use App\Models\WhatsappConversacion;
use App\Models\WhatsappFlujoNodo;
use App\Services\FechaLibreParser;
use App\Services\WhatsappBot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SolicitudReservaWhatsappController extends Controller
{
    public function index(Request $request)
    {
        $solicitudes = SolicitudReservaWhatsapp::latest()->get()->groupBy('estado');
        // Si la reserva se canceló después (desde el tablero de Reservas, o al
        // eliminar la solicitud), no cuenta como "anclada" aunque el vínculo
        // solicitud_whatsapp_id siga ahí — si no, el badge diría "Anclada en
        // Reservas" para un cuarto que en realidad ya está libre.
        $ancladas = Reserva::whereNotNull('solicitud_whatsapp_id')
            ->where('estado', '!=', 'Cancelada')
            ->pluck('solicitud_whatsapp_id')
            ->flip();

        // Si el admin está navegando semanas dentro del modal "Sugerir habitación"
        // (botones Semana anterior/siguiente), llega por querystring qué solicitud
        // y qué semana quiere ver — todas las demás solicitudes siguen mostrando
        // su semana por defecto (la que pidió el cliente).
        $solicitudSugerirId = $request->query('sugerir');
        $semanaSugerirOverride = $request->query('semana');

        $pendientes = $solicitudes->get(SolicitudReservaWhatsapp::PENDIENTE, collect());
        $semanaOverridePara = fn ($s) => ((string) $s->id === (string) $solicitudSugerirId) ? $semanaSugerirOverride : null;
        $disponibilidad = $pendientes->mapWithKeys(fn ($s) => [$s->id => $this->habitacionesConEstado($s, $semanaOverridePara($s))]);
        $semanasSugerencia = $pendientes->mapWithKeys(fn ($s) => [$s->id => $this->semanaBaseParaSugerencia($s, $semanaOverridePara($s))]);

        $confirmadas = $solicitudes->get(SolicitudReservaWhatsapp::CONFIRMADA, collect());

        return view('whatsapp.solicitudes', [
            'pendientes' => $pendientes,
            'esperandoPrenda' => $solicitudes->get(SolicitudReservaWhatsapp::APROBADA_ESPERANDO_PRENDA, collect()),
            'prendaEnRevision' => $solicitudes->get(SolicitudReservaWhatsapp::PRENDA_EN_REVISION, collect()),
            'confirmadas' => $confirmadas,
            'rechazadas' => $solicitudes->get(SolicitudReservaWhatsapp::RECHAZADA, collect()),
            'ancladas' => $ancladas,
            'disponibilidad' => $disponibilidad,
            'semanasSugerencia' => $semanasSugerencia,
            'sugerirAbiertoInicial' => $solicitudSugerirId,
        ]);
    }

    /**
     * Mejor fecha que se puede adivinar del texto libre de la semana — se usa para
     * el anclaje automático (ver anclarAutomatico()).
     */
    private function semanaSugerida(SolicitudReservaWhatsapp $solicitud): string
    {
        return FechaLibreParser::parse($solicitud->semana_texto)->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /**
     * Lunes de la semana a mostrar en el modal "Sugerir habitación": si viene un
     * override (el admin tocó Semana anterior/siguiente) se usa esa; si no, arranca
     * en la semana actual — igual que el tablero de Reserva Habitación — en vez de
     * adivinar la semana a partir del texto libre que mandó el cliente por WhatsApp
     * (ese texto es poco confiable: "esta semana", una fecha suelta, un día de la
     * semana... interpretarlo mal hacía que el modal mostrara disponibilidad de una
     * semana distinta a la real, y todo aparecía "disponible" aunque hubiera cuartos
     * ocupados de verdad). El staff ya puede navegar a la semana que el cliente pidió
     * con las flechas si no es la actual.
     */
    private function semanaBaseParaSugerencia(SolicitudReservaWhatsapp $solicitud, ?string $semanaOverride = null): Carbon
    {
        if ($semanaOverride) {
            try {
                return Carbon::parse($semanaOverride)->startOfWeek(Carbon::MONDAY)->startOfDay();
            } catch (\Throwable $e) {
                // ignora un override inválido y sigue con el fallback normal
            }
        }

        return now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    /**
     * Todos los cuartos, por sucursal, para la semana que pidió la solicitud (o la que
     * el admin esté navegando en el modal de sugerencia) — marcados como ocupados o
     * disponibles, igual que el tablero de Reservas.
     */
    private function habitacionesConEstado(SolicitudReservaWhatsapp $solicitud, ?string $semanaOverride = null): array
    {
        $semana = $this->semanaBaseParaSugerencia($solicitud, $semanaOverride);

        $reservasSemana = Reserva::whereDate('semana', $semana->toDateString())
            ->where('estado', '!=', 'Cancelada')
            ->get()
            ->keyBy('habitacion_id');

        return Sucursal::has('habitaciones')->orderBy('nombre')->get()
            ->map(fn ($sucursal) => [
                'sucursal' => $sucursal->nombre,
                'habitaciones' => Habitacion::where('sucursal_id', $sucursal->id)
                    ->orderByRaw('CAST(numero AS INTEGER)')
                    ->get()
                    ->map(function ($habitacion) use ($reservasSemana) {
                        $reserva = $reservasSemana->get($habitacion->id);

                        return ['numero' => $habitacion->numero, 'ocupada' => (bool) $reserva, 'cliente' => $reserva?->nombre];
                    }),
            ])->values()->all();
    }

    /**
     * Le sugiere al cliente, por WhatsApp, uno o varios cuartos alternativos al que
     * pidió (porque el suyo ya no está disponible), y deja la conversación esperando
     * que el cliente elija uno — si responde con el número de alguno de los sugeridos,
     * el bot actualiza la solicitud (local/cuarto) automáticamente (ver
     * WhatsappController::recibirCuartoSugerido). No cambia el estado de la solicitud.
     */
    public function sugerir(Request $request, SolicitudReservaWhatsapp $solicitud, WhatsappBot $bot)
    {
        $data = $request->validate([
            'seleccion'   => ['required', 'array', 'min:1'],
            'seleccion.*' => ['string'],
        ]);

        $sugeridos = collect($data['seleccion'])
            ->map(function ($item) {
                [$local, $cuarto] = array_pad(explode('|', $item, 2), 2, null);

                return ['local' => $local, 'cuarto' => $cuarto];
            })
            ->filter(fn ($s) => $s['local'] && $s['cuarto'])
            ->values();

        if ($sugeridos->isEmpty()) {
            return back()->withErrors(['solicitud' => 'Seleccioná al menos un cuarto disponible para sugerir.']);
        }

        $mensaje = $this->mensajeSugerencia($solicitud, $sugeridos);

        $resultado = $bot->enviar($solicitud->numero, $mensaje);

        if (! ($resultado['ok'] ?? false)) {
            return back()->withErrors(['solicitud' => 'No se pudo enviar la sugerencia: ' . ($resultado['error'] ?? 'error desconocido')]);
        }

        $solicitud->update(['cuartos_sugeridos' => $sugeridos->toArray()]);

        $nodoEspera = WhatsappFlujoNodo::where('clave', 'esperar_cuarto_sugerido')->first();
        $conversacion = WhatsappConversacion::firstOrCreate(['numero' => $solicitud->numero]);
        $conversacion->update([
            'nodo_actual_id' => $nodoEspera?->id,
            'solicitud_pendiente_id' => $solicitud->id,
        ]);

        return back()->with('status', 'Se le enviaron ' . $sugeridos->count() . ' opciones de cuarto al cliente.');
    }

    /**
     * Hay dos plantillas distintas según cuántos cuartos se sugieren: si es uno
     * solo, se le pide una confirmación directa (sí/no); si son varios, se le
     * pide que elija cuál de todos quiere por su número de cuarto.
     */
    private function mensajeSugerencia(SolicitudReservaWhatsapp $solicitud, \Illuminate\Support\Collection $sugeridos): string
    {
        $nombre = Str::before($solicitud->nombre, ' ');
        $intro = "Hola, {$nombre}. El cuarto que solicitaste ({$solicitud->local} — Cuarto {$solicitud->cuarto}) ya no está disponible.";

        if ($sugeridos->count() === 1) {
            $s = $sugeridos->first();

            return "{$intro}\n\nTenemos disponible:\n\n🏠 {$s['local']} — Cuarto {$s['cuarto']}\n\n¿Aceptas el cuarto {$s['cuarto']}?\n\nResponde:\n✅ *SI* _para aceptar_\n❌ *NO* _si no deseas este cuarto_";
        }

        $lista = $sugeridos->map(fn ($s) => "🏠 Cuarto {$s['cuarto']}")->implode("\n");
        $numerosLista = $sugeridos->pluck('cuarto');
        $numerosTexto = $numerosLista->slice(0, -1)->implode(', ') . ' o ' . $numerosLista->last();

        return "{$intro}\n\nPuedes elegir entre estos cuartos disponibles:\n\n{$lista}\n\nResponde directamente con el número del cuarto que aceptas: _{$numerosTexto}_.\n\nSi no deseas ninguno, responde *NO*.";
    }

    /**
     * Intenta anclar sola la solicitud al confirmarse (habitación + semana ya
     * están cargadas en la solicitud desde el flujo de WhatsApp) — ya no hay
     * paso manual. Si el cuarto/local no matchea con ningún cuarto real, o la
     * semana adivinada choca con otra reserva, no hace nada (la solicitud
     * queda marcada "Sin anclar" en el panel para que el admin la revise a
     * mano directo en Reservas) — nunca bloquea ni revierte la confirmación.
     */
    private function anclarAutomatico(SolicitudReservaWhatsapp $solicitud, int $usuarioId): void
    {
        if (Reserva::where('solicitud_whatsapp_id', $solicitud->id)->exists()) {
            return;
        }

        $habitacion = $this->resolverHabitacion($solicitud);

        if (! $habitacion) {
            return;
        }

        $semana = Carbon::parse($this->semanaSugerida($solicitud))->startOfWeek(Carbon::MONDAY)->startOfDay();

        if ($semana->lt(now()->startOfWeek(Carbon::MONDAY)) || $this->semanaOcupada($habitacion, $semana)) {
            return;
        }

        $this->crearReservaDesdeSolicitud($habitacion, $semana, $solicitud, $usuarioId);
    }

    private function resolverHabitacion(SolicitudReservaWhatsapp $solicitud): ?Habitacion
    {
        $sucursal = Sucursal::where('nombre', $solicitud->local)->first();

        return $sucursal
            ? Habitacion::where('sucursal_id', $sucursal->id)->where('numero', $solicitud->cuarto)->first()
            : null;
    }

    private function semanaOcupada(Habitacion $habitacion, Carbon $semana): bool
    {
        return Reserva::where('habitacion_id', $habitacion->id)
            ->whereDate('semana', $semana->toDateString())
            ->where('estado', '!=', 'Cancelada')
            ->exists();
    }

    /**
     * Crea la reserva "real" (tablero de habitaciones) a partir de una solicitud
     * confirmada por WhatsApp, para que quede visible en el módulo de Reservas.
     * Si la solicitud trae comprobante de prenda, se crea también el registro de
     * Prenda en depósito — cuando esa prenda se marque como devuelta, la solicitud
     * se elimina automáticamente de este listado (ver ReservaController::actualizarPrenda).
     */
    private function crearReservaDesdeSolicitud(Habitacion $habitacion, Carbon $semana, SolicitudReservaWhatsapp $solicitud, int $usuarioId): Reserva
    {
        $prendaId = null;
        if ($solicitud->prenda_comprobante) {
            // La solicitud ya pasó por revisión y confirmación del comprobante en el
            // panel de Solicitudes antes de poder anclarse, así que la prenda entra
            // directo como "confirmada" (no "pendiente") — ya no hace falta re-revisarla.
            $prenda = Prenda::create([
                'sucursal_id' => $habitacion->sucursal_id,
                'descripcion' => 'Depósito enviado por WhatsApp',
                'cliente' => $solicitud->nombre,
                'estado' => 'confirmada',
                'foto' => $solicitud->prenda_comprobante,
            ]);
            $prendaId = $prenda->id;
        }

        return Reserva::create([
            'habitacion_id' => $habitacion->id,
            'semana' => $semana->toDateString(),
            'nombre' => $solicitud->nombre,
            'cedula' => $solicitud->cedula,
            'telefono' => $solicitud->celular ?? '',
            'deposito_estado' => $solicitud->prenda_comprobante ? 'Comprobante enviado por WhatsApp' : '',
            'observaciones' => 'Reservado por WhatsApp',
            'foto_cedula' => $solicitud->foto_lateral,
            'foto_deposito' => $solicitud->prenda_comprobante,
            'prenda_id' => $prendaId,
            'usuario_id' => $usuarioId,
            'solicitud_whatsapp_id' => $solicitud->id,
        ]);
    }

    public function aprobar(Request $request, SolicitudReservaWhatsapp $solicitud, WhatsappBot $bot)
    {
        $solicitud->update([
            'estado' => SolicitudReservaWhatsapp::APROBADA_ESPERANDO_PRENDA,
            'revisada_por_id' => $request->user()->id,
            'revisada_en' => now(),
        ]);

        $conversacion = WhatsappConversacion::firstOrCreate(['numero' => $solicitud->numero]);
        $nodoEspera = WhatsappFlujoNodo::where('clave', 'esperar_comprobante_prenda')->first();
        $conversacion->update([
            'nodo_actual_id' => $nodoEspera?->id,
            'solicitud_pendiente_id' => $solicitud->id,
        ]);

        $plantilla = WhatsappFlujoNodo::where('clave', 'plantilla_aprobada')->first();
        $bot->enviar($solicitud->numero, $this->rellenarPlantilla($plantilla?->mensaje ?? '', $solicitud));

        return back()->with('status', 'Solicitud aprobada. Se le avisó al cliente para que deposite la prenda.');
    }

    public function rechazar(Request $request, SolicitudReservaWhatsapp $solicitud, WhatsappBot $bot)
    {
        $data = $request->validate([
            'motivo_rechazo' => ['nullable', 'string', 'max:500'],
        ]);

        $solicitud->update([
            'estado' => SolicitudReservaWhatsapp::RECHAZADA,
            'motivo_rechazo' => $data['motivo_rechazo'] ?? null,
            'revisada_por_id' => $request->user()->id,
            'revisada_en' => now(),
        ]);

        WhatsappConversacion::where('numero', $solicitud->numero)->update(['nodo_actual_id' => null]);

        $plantilla = WhatsappFlujoNodo::where('clave', 'plantilla_rechazada')->first();
        $mensaje = $this->rellenarPlantilla($plantilla?->mensaje ?? '', $solicitud);

        if (! empty($data['motivo_rechazo'])) {
            $mensaje .= "\n\nMotivo: " . $data['motivo_rechazo'];
        }

        $bot->enviar($solicitud->numero, $mensaje);

        return back()->with('status', 'Solicitud rechazada. Se le avisó al cliente.');
    }

    public function confirmarPrenda(Request $request, SolicitudReservaWhatsapp $solicitud, WhatsappBot $bot)
    {
        $solicitud->update([
            'estado' => SolicitudReservaWhatsapp::CONFIRMADA,
            'revisada_por_id' => $request->user()->id,
            'revisada_en' => now(),
        ]);

        WhatsappConversacion::where('numero', $solicitud->numero)->update([
            'nodo_actual_id' => null,
            'solicitud_pendiente_id' => null,
        ]);

        $this->anclarAutomatico($solicitud, $request->user()->id);

        $plantilla = WhatsappFlujoNodo::where('clave', 'plantilla_confirmada')->first();
        $bot->enviar($solicitud->numero, $this->rellenarPlantilla($plantilla?->mensaje ?? '', $solicitud));

        return back()->with('status', 'Reserva confirmada. Se le avisó al cliente.');
    }

    public function destroy(SolicitudReservaWhatsapp $solicitud)
    {
        foreach ([$solicitud->foto_lateral, $solicitud->foto_posterior, $solicitud->prenda_comprobante] as $archivo) {
            if ($archivo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($archivo);
            }
        }

        // Si esta solicitud ya estaba anclada en el tablero de Reservas, hay que
        // liberar esa habitación también — si no, el cuarto queda marcado como
        // ocupado para siempre aunque la solicitud de WhatsApp ya no exista.
        $seLiberoHabitacion = Reserva::where('solicitud_whatsapp_id', $solicitud->id)
            ->where('estado', '!=', 'Cancelada')
            ->update(['estado' => 'Cancelada']) > 0;

        $solicitud->delete();

        return back()->with('status', $seLiberoHabitacion
            ? 'Solicitud eliminada y habitación liberada en el tablero de Reservas.'
            : 'Solicitud eliminada.');
    }

    protected function rellenarPlantilla(string $mensaje, SolicitudReservaWhatsapp $solicitud): string
    {
        // {prenda} solo dice "recibida" si de verdad se pidió y llegó un comprobante —
        // si se confirmó saltando la prenda, no hay que decirle al cliente que se
        // recibió un depósito que nunca se le pidió.
        $prenda = $solicitud->prenda_comprobante ? "\n💵 Prenda: \$20 recibida" : '';

        return str_replace(
            ['{local}', '{cuarto}', '{semana}', '{prenda}'],
            [$solicitud->local, $solicitud->cuarto, $solicitud->semana_texto, $prenda],
            $mensaje
        );
    }
}
