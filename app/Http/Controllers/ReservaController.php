<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\Cliente;
use App\Models\Habitacion;
use App\Models\Prenda;
use App\Models\Reserva;
use App\Models\SolicitudReservaWhatsapp;
use App\Models\Sucursal;
use App\Services\WhatsappBot;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservaController extends Controller
{
    use ScopedToSucursal;

    public function index(Request $request)
    {
        $sucursales = $this->sucursalesConHabitaciones();
        $semana = $this->mondayOf($request->query('semana'));

        // "numero" es texto (admite etiquetas no numéricas), pero ordenamos numéricamente
        // para que 2 quede antes que 10 en vez de orden alfabético.
        $habitaciones = Habitacion::whereIn('sucursal_id', $sucursales->pluck('id'))
            ->orderByRaw('CAST(numero AS INTEGER)')
            ->get();

        $reservas = $habitaciones->isNotEmpty()
            ? Reserva::with('prenda')
                ->whereIn('habitacion_id', $habitaciones->pluck('id'))
                ->whereDate('semana', $semana->toDateString())
                ->where('estado', '!=', 'Cancelada')
                ->get()
                ->keyBy('habitacion_id')
            : collect();

        // Cada sucursal con habitaciones se muestra por separado (Casa Blanca, Casa Blanca VIP...),
        // cada una con su propio tablero de habitaciones/reservas de la semana.
        $hoteles = $sucursales->map(fn ($s) => [
            'sucursal' => $s,
            'habitaciones' => $habitaciones->where('sucursal_id', $s->id)->values(),
        ]);

        $prendas = Prenda::where('estado', '!=', 'devuelta')->with('reserva.habitacion')->latest()->limit(20)->get();
        $clientes = Cliente::latest()->limit(20)->get();

        // Para mostrar "cuarto y semana" en el modal "Ver detalles" del cliente:
        // el Cliente no tiene relación directa a Reserva, así que se busca por
        // cédula la reserva no cancelada más reciente de cada uno.
        $ultimaReservaPorCedula = Reserva::with('habitacion')
            ->whereIn('cedula', $clientes->pluck('cedula'))
            ->where('estado', '!=', 'Cancelada')
            ->orderByDesc('semana')
            ->get()
            ->groupBy('cedula')
            ->map(fn ($grupo) => $grupo->first());

        return view('reservas.index', [
            'hoteles' => $hoteles,
            'semana' => $semana,
            'semanaAnterior' => $semana->copy()->subWeek()->toDateString(),
            'semanaSiguiente' => $semana->copy()->addWeek()->toDateString(),
            'reservas' => $reservas,
            'prendas' => $prendas,
            'clientes' => $clientes,
            'ultimaReservaPorCedula' => $ultimaReservaPorCedula,
        ]);
    }

    public function guardarPrenda(Request $request)
    {
        $data = $request->validate([
            'cliente_id'   => ['required', 'exists:clientes,id'],
            'dejo_prenda'  => ['required', 'in:si,no'],
            'descripcion'  => ['required_if:dejo_prenda,si', 'nullable', 'string', 'max:255'],
        ]);

        if ($data['dejo_prenda'] === 'no') {
            return back()->with('status', 'Registrado: el cliente no dejó ninguna prenda en depósito.');
        }

        $cliente = Cliente::findOrFail($data['cliente_id']);
        $sucursal = $this->sucursalActual($request);

        Prenda::create([
            'sucursal_id' => $sucursal?->id,
            'descripcion' => $data['descripcion'],
            'cliente'     => $cliente->nombre_completo,
            'estado'      => 'confirmada',
        ]);

        return back()->with('status', 'Prenda registrada correctamente.');
    }

    public function actualizarPrenda(Request $request, Prenda $prenda, WhatsappBot $bot)
    {
        $data = $request->validate([
            'estado' => ['required', 'in:confirmada,devuelta'],
            'foto'   => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('prendas', 'public');
        }

        $data['devuelta_at'] = $data['estado'] === 'devuelta' ? now() : null;

        $eraDevuelta = $prenda->estado === 'devuelta';
        $reserva = Reserva::where('prenda_id', $prenda->id)->first();

        $prenda->update($data);

        if ($data['estado'] === 'devuelta' && ! $eraDevuelta) {
            $this->avisarPrendaDevuelta($prenda, $reserva, $bot);
        }

        // Si esta prenda venía de una reserva anclada desde una solicitud de WhatsApp,
        // al devolverse ya no hace falta seguir mostrando esa solicitud en el panel.
        if ($data['estado'] === 'devuelta' && $reserva?->solicitud_whatsapp_id) {
            SolicitudReservaWhatsapp::whereKey($reserva->solicitud_whatsapp_id)->delete();
        }

        return back()->with('status', 'Prenda actualizada correctamente.');
    }

    /**
     * Le avisa por WhatsApp al cliente que su prenda/depósito ya fue devuelto,
     * adjuntando la foto que el admin cargó al marcarla como devuelta (o la que
     * ya tenía guardada). Si no hay forma confiable de saber su número, no hace nada.
     */
    private function avisarPrendaDevuelta(Prenda $prenda, ?Reserva $reserva, WhatsappBot $bot): void
    {
        $numero = null;

        if ($reserva?->solicitud_whatsapp_id) {
            $numero = SolicitudReservaWhatsapp::find($reserva->solicitud_whatsapp_id)?->numero;
        }

        if (! $numero) {
            $numero = $this->normalizarNumeroEcuador($reserva->telefono ?? null);
        }

        if (! $numero) {
            return;
        }

        $nombre = $reserva->nombre ?? $prenda->cliente ?? '';
        $mensaje = trim("Hola {$nombre}, te confirmamos que se devolvió tu prenda: {$prenda->descripcion}. ¡Gracias!");
        $fotoUrl = $prenda->foto ? asset('storage/' . $prenda->foto) : null;

        $bot->enviar($numero, $mensaje, $fotoUrl);
    }

    private function normalizarNumeroEcuador(?string $telefono): ?string
    {
        if (! $telefono) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $telefono);

        if (str_starts_with($digitos, '593') && strlen($digitos) === 12) {
            return $digitos;
        }

        if (str_starts_with($digitos, '0') && strlen($digitos) === 10) {
            return '593' . substr($digitos, 1);
        }

        if (strlen($digitos) === 9) {
            return '593' . $digitos;
        }

        return null;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'habitacion_id'    => ['required', 'exists:habitaciones,id'],
            'semana'           => ['required', 'date'],
            'nombre'           => ['required', 'string', 'max:255'],
            'cedula'           => ['required', 'string', 'max:50'],
            'deposito_estado'  => ['nullable', 'string', 'max:255'],
            'observaciones'    => ['nullable', 'string'],
            'foto_cedula'      => ['nullable', 'image', 'max:4096'],
            'foto_deposito'    => ['nullable', 'image', 'max:4096'],
        ]);

        // La sucursal de la reserva/prenda es la de la habitación elegida, no la sucursal
        // "actual" de la sesión: el selector de Reservas puede apuntar a una sucursal
        // distinta de la del menú general.
        $habitacion = Habitacion::findOrFail($data['habitacion_id']);
        $sucursal = $habitacion->sucursal;

        $semana = $this->mondayOf($data['semana']);

        $existente = Reserva::where('habitacion_id', $data['habitacion_id'])
            ->whereDate('semana', $semana->toDateString())
            ->where('estado', '!=', 'Cancelada')
            ->exists();

        if ($existente) {
            return back()->withErrors(['habitacion_id' => 'Esa habitación ya está reservada para esa semana.']);
        }

        $depositoEstado = trim($data['deposito_estado'] ?? '');
        $fotoDepositoPath = $request->hasFile('foto_deposito') ? $request->file('foto_deposito')->store('prendas', 'public') : null;

        $prendaId = null;

        if ($this->hasPrenda($depositoEstado)) {
            $prenda = Prenda::create([
                'sucursal_id' => $sucursal->id,
                'descripcion' => $depositoEstado,
                'cliente'     => $data['nombre'],
                'estado'      => 'confirmada',
                'foto'        => $fotoDepositoPath,
            ]);
            $prendaId = $prenda->id;
        }

        Reserva::create([
            'habitacion_id'   => $data['habitacion_id'],
            'semana'          => $semana->toDateString(),
            'nombre'          => $data['nombre'],
            'cedula'          => $data['cedula'],
            'telefono'        => '',
            'deposito_estado' => $depositoEstado,
            'observaciones'   => $data['observaciones'] ?? null,
            'foto_cedula'     => $request->hasFile('foto_cedula') ? $request->file('foto_cedula')->store('reservas', 'public') : null,
            'foto_deposito'   => $fotoDepositoPath,
            'prenda_id'       => $prendaId,
            'usuario_id'      => $request->user()->id,
        ]);

        return redirect()->route('reservas', ['semana' => $semana->toDateString()])->with('status', 'Reserva registrada correctamente.');
    }

    public function cancelar(Reserva $reserva)
    {
        $reserva->update(['estado' => 'Cancelada']);

        return back()->with('status', 'Reserva cancelada.');
    }

    public function actualizar(Request $request, Reserva $reserva)
    {
        $data = $request->validate([
            'nombre'          => ['required', 'string', 'max:255'],
            'cedula'          => ['required', 'string', 'max:50'],
            'deposito_estado' => ['nullable', 'string', 'max:255'],
            'observaciones'   => ['nullable', 'string'],
        ]);

        $depositoEstado = trim($data['deposito_estado'] ?? '');

        // Si al editar se marca "Sí dejó depósito" y la reserva todavía no tiene una
        // prenda anclada (p. ej. se creó como "No" y luego se corrigió), se crea acá
        // igual que en store(); si ya tenía una, solo se actualiza su descripción.
        if ($this->hasPrenda($depositoEstado)) {
            if ($reserva->prenda_id) {
                Prenda::whereKey($reserva->prenda_id)->update(['descripcion' => $depositoEstado]);
            } else {
                $prenda = Prenda::create([
                    'sucursal_id' => $reserva->habitacion->sucursal_id,
                    'descripcion' => $depositoEstado,
                    'cliente'     => $data['nombre'],
                    'estado'      => 'confirmada',
                ]);
                $data['prenda_id'] = $prenda->id;
            }
        }

        $reserva->update($data);

        return back()->with('status', 'Datos del cliente actualizados correctamente.');
    }

    public function destroyCliente(Cliente $cliente)
    {
        $cliente->delete();

        return back()->with('status', 'Cliente eliminado correctamente.');
    }

    private function mondayOf(?string $fecha): Carbon
    {
        $fecha = $fecha ? Carbon::parse($fecha) : now();

        return $fecha->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    private function hasPrenda(string $depositoEstado): bool
    {
        $normalizado = mb_strtolower(trim($depositoEstado));

        return ! in_array($normalizado, ['', 'no', 'sin prenda', 'vacío', 'vacio'], true);
    }

    /**
     * Reservas solo aplica a las sucursales que manejan habitaciones (hoy: Casa Blanca
     * y Casa Blanca VIP) — el resto (bodegas, billar) no aparecen acá aunque sí están
     * disponibles en el selector general de sucursal del menú.
     */
    private function sucursalesConHabitaciones()
    {
        return Sucursal::has('habitaciones')->orderBy('nombre')->get();
    }
}
