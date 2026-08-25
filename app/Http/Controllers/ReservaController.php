<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Habitacion;
use App\Models\Prenda;
use App\Models\Reserva;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservaController extends Controller
{
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

        $prendas = Prenda::latest()->limit(20)->get();
        $clientes = Cliente::latest()->limit(20)->get();

        // Para el listado imprimible: qué clientes dejaron prenda (depósito), identificados
        // por cédula a través de las reservas que sí quedaron con una prenda asociada.
        $cedulasConPrenda = Reserva::whereNotNull('prenda_id')->pluck('cedula');

        // Y en qué habitación está cada cliente esta semana (por cédula, misma reserva activa
        // que ya se cargó arriba para el tablero de habitaciones).
        $habitacionPorCedula = $reservas->mapWithKeys(fn ($reserva, $habitacionId) => [
            $reserva->cedula => $habitaciones->firstWhere('id', $habitacionId)?->numero,
        ]);

        return view('reservas.index', [
            'hoteles' => $hoteles,
            'semana' => $semana,
            'semanaAnterior' => $semana->copy()->subWeek()->toDateString(),
            'semanaSiguiente' => $semana->copy()->addWeek()->toDateString(),
            'reservas' => $reservas,
            'habitacionPorCedula' => $habitacionPorCedula,
            'prendas' => $prendas,
            'clientes' => $clientes,
            'cedulasConPrenda' => $cedulasConPrenda,
        ]);
    }

    public function actualizarPrenda(Request $request, Prenda $prenda)
    {
        $data = $request->validate([
            'estado' => ['required', 'in:pendiente,devuelta'],
            'foto'   => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('prendas', 'public');
        }

        $data['devuelta_at'] = $data['estado'] === 'devuelta' ? now() : null;

        $prenda->update($data);

        return back()->with('status', 'Prenda actualizada correctamente.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'habitacion_id'    => ['required', 'exists:habitaciones,id'],
            'semana'           => ['required', 'date'],
            'nombre'           => ['required', 'string', 'max:255'],
            'cedula'           => ['required', 'string', 'max:50'],
            'telefono'         => ['required', 'string', 'max:50'],
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
                'estado'      => 'pendiente',
                'foto'        => $fotoDepositoPath,
            ]);
            $prendaId = $prenda->id;
        }

        Reserva::create([
            'habitacion_id'   => $data['habitacion_id'],
            'semana'          => $semana->toDateString(),
            'nombre'          => $data['nombre'],
            'cedula'          => $data['cedula'],
            'telefono'        => $data['telefono'],
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
