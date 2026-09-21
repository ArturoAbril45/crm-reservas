<x-app-layout>
    <x-slot name="header">
        Reserva Habitación
    </x-slot>

    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            .lg\:ml-\[272px\] { margin-left: 0 !important; }
            main { margin: 0 !important; padding: 0 !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>

    <div x-data="{
            abierto: false,
            habitacionId: null,
            habitacionNumero: '',
            dejoPrenda: 'no',
            detalleDeposito: '',
            detalleAbierto: false,
            detalle: {},
            verDetalle(cliente) {
                this.detalle = cliente;
                this.detalleAbierto = true;
            },
            verFotos: null,
            eliminarAbierto: false,
            reservaEliminarId: null,
            reservaEliminarInfo: '',
            confirmarEliminar(reserva) {
                this.reservaEliminarId = reserva.id;
                this.reservaEliminarInfo = reserva.nombre + ' — Cuarto ' + reserva.numero;
                this.eliminarAbierto = true;
            },
            editarAbierto: false,
            reservaEditar: {},
            abrirEditar(reserva) {
                this.reservaEditar = reserva;
                this.editarAbierto = true;
            },
         }">

    @if (session('status'))
        <div class="no-print mb-6 px-4 py-3 rounded-xl bg-[#9c0720]/10 text-[#9c0720] text-sm border border-[#9c0720]/15">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="no-print mb-6 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Navegación de semana -->
    <div class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
        <a href="{{ route('reservas', ['semana' => $semanaAnterior]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m15 18-6-6 6-6" /></svg>
            Semana anterior
        </a>

        <div class="text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wider">Semana del</p>
            <p class="font-semibold text-gray-900">{{ $semana->format('d/m/Y') }} — {{ $semana->copy()->addDays(6)->format('d/m/Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="rounded-lg border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                Imprimir
            </button>
            <a href="{{ route('reservas', ['semana' => $semanaSiguiente]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                Semana siguiente
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m9 18 6-6-6-6" /></svg>
            </a>
        </div>
    </div>

    @if ($hoteles->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
            Todavía no hay ninguna sucursal con habitaciones cargadas.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
            <!-- Habitaciones, una sección por sucursal -->
            @foreach ($hoteles as $hotel)
                <div class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 overflow-visible h-fit">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                        <h2 class="font-semibold text-gray-900">Reserva Habitación — {{ $hotel['sucursal']->nombre }}</h2>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-green-500"></span> Disponible</span>
                            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span> Ocupada</span>
                        </div>
                    </div>

                    <div class="p-6">
                        @if ($hotel['habitaciones']->count())
                            <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 gap-1.5" x-data="{ detalle: null }">
                                @foreach ($hotel['habitaciones'] as $habitacion)
                                    @php $reserva = $reservas->get($habitacion->id); @endphp
                                    @if ($reserva)
                                        <div class="relative aspect-square rounded-md border border-red-600 bg-red-500 p-0.5 flex flex-col items-center justify-center text-center cursor-pointer"
                                             @click="detalle = (detalle === {{ $habitacion->id }} ? null : {{ $habitacion->id }})">
                                            <span class="font-semibold text-white text-[9px] leading-tight">Cuarto {{ $habitacion->numero }}</span>
                                            <span class="text-[8px] text-white/90 truncate w-full leading-tight">{{ \Illuminate\Support\Str::before($reserva->nombre, ' ') }}</span>

                                            <div x-show="detalle === {{ $habitacion->id }}" x-cloak @click.outside="detalle = null" @click.stop
                                                 class="absolute z-10 top-full left-1/2 -translate-x-1/2 mt-1 w-36 rounded-lg border border-gray-100 bg-white p-1.5 text-left shadow-lg cursor-default">
                                                <button type="button"
                                                        @click="detalle = null; abrirEditar({{ \Illuminate\Support\Js::from([
                                                            'id' => $reserva->id,
                                                            'numero' => $habitacion->numero,
                                                            'nombre' => $reserva->nombre,
                                                            'cedula' => $reserva->cedula,
                                                            'deposito_estado' => $reserva->deposito_estado,
                                                            'observaciones' => $reserva->observaciones,
                                                        ]) }})"
                                                        class="w-full text-left px-2.5 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                    Editar
                                                </button>
                                                <button type="button"
                                                        @click="detalle = null; confirmarEliminar({{ \Illuminate\Support\Js::from([
                                                            'id' => $reserva->id,
                                                            'numero' => $habitacion->numero,
                                                            'nombre' => $reserva->nombre,
                                                        ]) }})"
                                                        class="w-full text-left px-2.5 py-2 rounded-md text-sm font-medium text-red-600 hover:bg-red-50">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <button type="button" @click="abierto = true; habitacionId = {{ $habitacion->id }}; habitacionNumero = '{{ $habitacion->numero }}'; dejoPrenda = 'no'; detalleDeposito = ''"
                                                class="aspect-square rounded-md border border-green-600 bg-green-500 flex items-center justify-center hover:bg-green-600 transition-colors p-0.5">
                                            <span class="font-semibold text-white text-[9px] leading-tight">Cuarto {{ $habitacion->numero }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-sm text-gray-400 py-6">Esta sucursal todavía no tiene habitaciones cargadas.</p>
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Reservas de la semana (imprimible) — una "hoja" por sucursal -->
            @php
                $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                $mesesAnio = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                $finSemana = $semana->copy()->addDays(6);
                $tituloSemana = 'SEMANA DE RESERVA: '
                    . strtoupper($diasSemana[$semana->dayOfWeekIso - 1]) . ' ' . $semana->day
                    . ' AL ' . strtoupper($diasSemana[$finSemana->dayOfWeekIso - 1]) . ' ' . $finSemana->day
                    . ' DE ' . strtoupper($mesesAnio[$finSemana->month]) . ' DE ' . $finSemana->year;
            @endphp
            @foreach ($hoteles as $hotel)
                @php
                    $esVip = str_contains(mb_strtoupper($hotel['sucursal']->nombre), 'VIP');
                    $ocupados = $hotel['habitaciones']->filter(fn ($h) => $reservas->has($h->id))->count();
                    $disponibles = $hotel['habitaciones']->count() - $ocupados;
                @endphp
                <div class="hidden print:block text-black bg-white p-6" style="{{ !$loop->first ? 'break-before: page;' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="h-12 w-12 rounded-full bg-[#9c0720] flex items-center justify-center text-white font-bold text-base shrink-0">CB</div>
                            <div>
                                <p class="font-extrabold text-base leading-tight">CASA BLANCA</p>
                                <p class="text-[9px] uppercase tracking-widest text-gray-500 leading-tight">Nithg Club</p>
                            </div>
                        </div>
                        <p class="text-xs italic text-gray-500">Diversión con Estilo</p>
                    </div>

                    <h1 class="text-2xl font-extrabold mt-2 leading-tight">
                        CASA BLANCA @if ($esVip)<span class="text-[#c9971f]">VIP</span>@endif
                    </h1>
                    <p class="text-xs font-semibold text-gray-700 mt-0.5">{{ $tituloSemana }}</p>

                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 text-white text-xs font-bold px-3 py-1">
                            DISPONIBLES <span class="bg-white/25 rounded px-1.5">{{ $disponibles }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 text-white text-xs font-bold px-3 py-1">
                            OCUPADOS <span class="bg-white/25 rounded px-1.5">{{ $ocupados }}</span>
                        </span>
                    </div>

                    <table class="w-full text-xs border-collapse mt-2.5 leading-tight">
                        <thead>
                            <tr class="bg-[#9c0720] text-white">
                                <th class="text-left py-1 px-3 font-semibold">N.° DE CUARTO</th>
                                <th class="text-left py-1 px-3 font-semibold">NOMBRE DE LA CHICA</th>
                                <th class="text-left py-1 px-3 font-semibold">CÉDULA</th>
                                <th class="text-left py-1 px-3 font-semibold">PRENDA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($hotel['habitaciones'] as $habitacion)
                                @php $reserva = $reservas->get($habitacion->id); @endphp
                                <tr class="{{ $loop->even ? 'bg-gray-100' : 'bg-white' }}">
                                    <td class="py-0.5 px-3 font-semibold">{{ $habitacion->numero }}</td>
                                    <td class="py-0.5 px-3">{{ $reserva->nombre ?? '' }}</td>
                                    <td class="py-0.5 px-3">{{ $reserva->cedula ?? '' }}</td>
                                    <td class="py-0.5 px-3">{{ $reserva ? ($reserva->prenda_id ? 'SÍ' : 'NO') : '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 px-3 text-center text-gray-400">Esta sucursal todavía no tiene habitaciones cargadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-gray-300">
                        <p class="text-xs italic text-gray-600">
                            {{ $esVip ? 'La mejor noche, siempre en Casa Blanca' : 'Gracias por ser parte de Casa Blanca' }}
                        </p>
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 text-white text-[10px] font-bold px-2.5 py-1">
                                TOTAL DE CUARTOS {{ $hotel['habitaciones']->count() }}
                            </span>
                            <p class="text-[9px] text-gray-400 mt-0.5">Reporte generado por el sistema de reservas</p>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Clientes -->
            <div class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Clientes</h2>
                </div>
                <div class="overflow-x-auto max-h-80 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Nombre completo</th>
                                <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Cédula</th>
                                <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Cédula (fotos)</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clientes as $cliente)
                                <tr class="border-b border-gray-50 last:border-0">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $cliente->nombre_completo }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ $cliente->cedula }}</td>
                                    <td class="px-6 py-4 text-xs">
                                        @if ($cliente->foto_cedula_frontal || $cliente->foto_cedula_trasera)
                                            <button type="button"
                                                    @click="verFotos = {{ \Illuminate\Support\Js::from(array_values(array_filter([
                                                        $cliente->foto_cedula_frontal ? asset('storage/' . $cliente->foto_cedula_frontal) : null,
                                                        $cliente->foto_cedula_trasera ? asset('storage/' . $cliente->foto_cedula_trasera) : null,
                                                    ]))) }}"
                                                    class="text-[#9c0720] hover:text-[#7c0519] font-medium">Ver cédula</button>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @php $ultimaReserva = $ultimaReservaPorCedula[$cliente->cedula] ?? null; @endphp
                                            <button type="button"
                                                    @click="verDetalle({{ \Illuminate\Support\Js::from([
                                                        'nombre_completo' => $cliente->nombre_completo,
                                                        'cedula' => $cliente->cedula,
                                                        'foto_cedula_frontal' => $cliente->foto_cedula_frontal ? asset('storage/' . $cliente->foto_cedula_frontal) : null,
                                                        'foto_cedula_trasera' => $cliente->foto_cedula_trasera ? asset('storage/' . $cliente->foto_cedula_trasera) : null,
                                                        'habitacion' => $ultimaReserva?->habitacion?->numero,
                                                        'semana' => $ultimaReserva ? $ultimaReserva->semana->format('d/m/Y') . ' al ' . $ultimaReserva->semana->copy()->addDays(6)->format('d/m/Y') : null,
                                                    ]) }})"
                                                    class="text-sm font-medium text-[#9c0720] hover:text-[#7c0519]">
                                                Ver detalles
                                            </button>
                                            <form method="POST" action="{{ route('clientes.destroy', $cliente) }}"
                                                  onsubmit="return confirm('¿Eliminar a {{ $cliente->nombre_completo }}? Esto no borra sus ventas o reservas anteriores, pero no va a poder relacionarlas con este cliente. No se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">Todavía no hay clientes registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            </div>

            <!-- Prendas -->
            <div x-data="{
                    abierto: false,
                    baseUrl: '{{ url('/prendas') }}',
                    id: null,
                    descripcion: '',
                    estado: 'confirmada',
                    editar(prenda) {
                        this.id = prenda.id;
                        this.descripcion = prenda.descripcion;
                        this.estado = prenda.estado;
                        this.abierto = true;
                    },
                    nuevaAbierta: false,
                    clienteId: '',
                    dejoPrenda: 'si',
                    nuevaDescripcion: '',
                    abrirNueva() {
                        this.clienteId = '';
                        this.dejoPrenda = 'si';
                        this.nuevaDescripcion = '';
                        this.nuevaAbierta = true;
                    },
                 }"
                 class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-fit">

                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900">Prendas pendientes</h2>
                    <button type="button" @click="abrirNueva()" class="text-xs font-semibold text-[#9c0720] hover:text-[#7c0519]">
                        + Nueva prenda
                    </button>
                </div>

                <ul class="divide-y divide-gray-50 max-h-[420px] overflow-y-auto">
                    @forelse ($prendas as $prenda)
                        <li class="px-6 py-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $prenda->descripcion }}</p>
                                <p class="text-xs text-gray-500">{{ $prenda->cliente ?? '—' }} · {{ $prenda->created_at->format('d/m/Y') }}</p>
                                @if ($prenda->reserva)
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Cuarto {{ $prenda->reserva->habitacion->numero ?? '—' }}
                                        · Semana {{ $prenda->reserva->semana->format('d') }} al {{ $prenda->reserva->semana->copy()->addDays(6)->format('d/m') }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <span @class([
                                        'ml-1 font-medium',
                                        'text-green-700' => $prenda->estado === 'devuelta',
                                        'text-[#9c0720]' => $prenda->estado !== 'devuelta',
                                    ])>
                                        {{ match($prenda->estado) { 'devuelta' => 'Devuelta', default => 'Confirmada' } }}
                                    </span>
                                </p>
                            </div>
                            <div class="shrink-0 flex items-center gap-3">
                                @if ($prenda->foto)
                                    <button @click="verFotos = [{{ \Illuminate\Support\Js::from(asset('storage/' . $prenda->foto)) }}]"
                                            class="text-xs font-medium text-[#9c0720] hover:text-[#7c0519]">
                                        Ver prenda
                                    </button>
                                @endif
                                <button @click="editar({{ \Illuminate\Support\Js::from($prenda->only(['id', 'descripcion', 'estado'])) }})"
                                        class="text-xs font-medium text-[#9c0720] hover:text-[#7c0519]">
                                    Editar
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-400">Todavía no hay prendas registradas.</li>
                    @endforelse
                </ul>

                <!-- Modal nueva prenda -->
                <div x-show="nuevaAbierta" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
                    <div @click.outside="nuevaAbierta = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900">Nueva prenda</h3>
                            <button @click="nuevaAbierta = false" class="text-gray-400 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M18 6 6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('prendas.store') }}" class="space-y-4">
                            @csrf

                            <div>
                                <x-input-label for="cliente_id" value="Cliente" />
                                <select id="cliente_id" name="cliente_id" x-model="clienteId" required
                                        class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                                    <option value="" disabled>Seleccioná un cliente...</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->id }}">{{ $cliente->nombre_completo }} — {{ $cliente->cedula }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label value="¿Dejó prenda?" />
                                <div class="mt-1.5 grid grid-cols-2 gap-2">
                                    <label class="flex items-center justify-center gap-2 rounded-lg border px-3.5 py-2.5 text-sm cursor-pointer"
                                           :class="dejoPrenda === 'si' ? 'border-[#9c0720] bg-[#9c0720]/10 text-[#9c0720] font-medium' : 'border-gray-200 text-gray-600'">
                                        <input type="radio" name="dejo_prenda" value="si" x-model="dejoPrenda" class="sr-only">
                                        Sí
                                    </label>
                                    <label class="flex items-center justify-center gap-2 rounded-lg border px-3.5 py-2.5 text-sm cursor-pointer"
                                           :class="dejoPrenda === 'no' ? 'border-[#9c0720] bg-[#9c0720]/10 text-[#9c0720] font-medium' : 'border-gray-200 text-gray-600'">
                                        <input type="radio" name="dejo_prenda" value="no" x-model="dejoPrenda" class="sr-only">
                                        No
                                    </label>
                                </div>
                            </div>

                            <div x-show="dejoPrenda === 'si'">
                                <x-input-label for="descripcion" value="¿Qué prenda dejó?" />
                                <x-text-input id="descripcion" name="descripcion" type="text" class="mt-1.5" x-model="nuevaDescripcion" placeholder="Ej. Cédula, reloj, campera azul..." />
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="nuevaAbierta = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <x-primary-button class="flex-1 justify-center">Guardar</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal editar prenda -->
                <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
                    <div @click.outside="abierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900">Editar prenda</h3>
                            <button @click="abierto = false" class="text-gray-400 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M18 6 6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <p class="text-sm text-gray-500 mb-4" x-text="descripcion"></p>

                        <form method="POST" :action="baseUrl + '/' + id + '/actualizar'" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div>
                                <x-input-label value="Estado" />
                                <select name="estado" x-model="estado" class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                                    <option value="confirmada">Confirmada</option>
                                    <option value="devuelta">Devuelta</option>
                                </select>
                            </div>

                            <div x-show="estado === 'devuelta'">
                                <x-input-label for="foto" value="Foto de la prenda devuelta" />
                                <input id="foto" name="foto" type="file" accept="image/*"
                                       class="mt-1.5 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#9c0720]/10 file:text-[#9c0720] file:text-sm file:font-medium">
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="abierto = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <x-primary-button class="flex-1">Guardar</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal reservar -->
    <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8 overflow-y-auto" style="display: none;">
        <div @click.outside="abierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 my-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Reservar habitación <span x-text="habitacionNumero"></span></h3>
                <button @click="abierto = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('reservas.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="habitacion_id" :value="habitacionId">
                <input type="hidden" name="semana" value="{{ $semana->toDateString() }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1.5" required :value="old('nombre')" />
                    </div>
                    <div>
                        <x-input-label for="cedula" value="Cédula" />
                        <x-text-input id="cedula" name="cedula" type="text" class="mt-1.5" required :value="old('cedula')" />
                    </div>
                </div>

                <div>
                    <x-input-label value="¿Dejó depósito / prenda?" />
                    <div class="mt-1.5 flex gap-2">
                        <button type="button" @click="dejoPrenda = 'si'"
                                class="flex-1 py-2.5 rounded-lg border text-sm font-medium"
                                :class="dejoPrenda === 'si' ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                            Sí
                        </button>
                        <button type="button" @click="dejoPrenda = 'no'; detalleDeposito = ''"
                                class="flex-1 py-2.5 rounded-lg border text-sm font-medium"
                                :class="dejoPrenda === 'no' ? 'bg-gray-700 border-gray-700 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                            No
                        </button>
                    </div>
                    <div x-show="dejoPrenda === 'si'" x-cloak class="mt-2">
                        <x-text-input name="deposito_detalle_ui" type="text" x-model="detalleDeposito" placeholder="¿Qué dejó? (opcional) — ej. Cédula, reloj" />
                    </div>
                    <input type="hidden" name="deposito_estado" :value="dejoPrenda === 'si' ? (detalleDeposito || 'Sí dejó depósito') : 'No'">
                    <p class="mt-1 text-xs text-gray-400">Si marcás "Sí", queda anclado en Prendas como depósito pendiente.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="foto_cedula" value="Foto de cédula" />
                        <input id="foto_cedula" name="foto_cedula" type="file" accept="image/*"
                               class="mt-1.5 block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-[#9c0720]/10 file:text-[#9c0720] file:text-xs file:font-medium">
                    </div>
                    <div>
                        <x-input-label for="foto_deposito" value="Foto del depósito" />
                        <input id="foto_deposito" name="foto_deposito" type="file" accept="image/*"
                               class="mt-1.5 block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-[#9c0720]/10 file:text-[#9c0720] file:text-xs file:font-medium">
                    </div>
                </div>

                <div>
                    <x-input-label for="observaciones" value="Observaciones" />
                    <textarea id="observaciones" name="observaciones" rows="2"
                              class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">{{ old('observaciones') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="abierto = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <x-primary-button class="flex-1 justify-center">Reservar</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal confirmar eliminar reserva -->
    <div x-show="eliminarAbierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
        <div @click.outside="eliminarAbierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
            <h3 class="font-semibold text-gray-900">¿Eliminar esta reserva?</h3>
            <p class="text-sm text-gray-500 mt-2">
                Se va a liberar la habitación y la reserva de <span class="font-medium text-gray-700" x-text="reservaEliminarInfo"></span> quedará cancelada. Esta acción no se puede deshacer.
            </p>
            <div class="flex gap-3 pt-5">
                <button type="button" @click="eliminarAbierto = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Cancelar
                </button>
                <form method="POST" class="flex-1" :action="'{{ route('reservas.cancelar', ['reserva' => 999999]) }}'.replace('999999', reservaEliminarId)">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                        Sí, eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal editar datos del cliente -->
    <div x-show="editarAbierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8 overflow-y-auto" style="display: none;">
        <div @click.outside="editarAbierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 my-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Editar cliente — Cuarto <span x-text="reservaEditar.numero"></span></h3>
                <button @click="editarAbierto = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" :action="'{{ route('reservas.actualizar', ['reserva' => 999999]) }}'.replace('999999', reservaEditar.id)" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="editar_nombre" value="Nombre" />
                        <x-text-input id="editar_nombre" name="nombre" type="text" class="mt-1.5" required x-model="reservaEditar.nombre" />
                    </div>
                    <div>
                        <x-input-label for="editar_cedula" value="Cédula" />
                        <x-text-input id="editar_cedula" name="cedula" type="text" class="mt-1.5" required x-model="reservaEditar.cedula" />
                    </div>
                </div>

                <div>
                    <x-input-label value="¿Dejó depósito / prenda?" />
                    <div class="mt-1.5 flex gap-2">
                        <button type="button"
                                @click="reservaEditar.deposito_estado = (reservaEditar.deposito_estado && reservaEditar.deposito_estado.toLowerCase() !== 'no' ? reservaEditar.deposito_estado : 'Sí dejó depósito')"
                                class="flex-1 py-2.5 rounded-lg border text-sm font-medium"
                                :class="(reservaEditar.deposito_estado && reservaEditar.deposito_estado.toLowerCase() !== 'no') ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                            Sí
                        </button>
                        <button type="button" @click="reservaEditar.deposito_estado = 'No'"
                                class="flex-1 py-2.5 rounded-lg border text-sm font-medium"
                                :class="(!reservaEditar.deposito_estado || reservaEditar.deposito_estado.toLowerCase() === 'no') ? 'bg-gray-700 border-gray-700 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                            No
                        </button>
                    </div>
                    <div x-show="reservaEditar.deposito_estado && reservaEditar.deposito_estado.toLowerCase() !== 'no'" x-cloak class="mt-2">
                        <x-text-input id="editar_deposito_estado" name="deposito_estado" type="text" x-model="reservaEditar.deposito_estado" placeholder="¿Qué dejó? (opcional) — ej. Cédula, reloj" />
                    </div>
                    <template x-if="!reservaEditar.deposito_estado || reservaEditar.deposito_estado.toLowerCase() === 'no'">
                        <input type="hidden" name="deposito_estado" value="No">
                    </template>
                </div>

                <div>
                    <x-input-label for="editar_observaciones" value="Observaciones" />
                    <textarea id="editar_observaciones" name="observaciones" rows="2" x-model="reservaEditar.observaciones"
                              class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]"></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="editarAbierto = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <x-primary-button class="flex-1 justify-center">Guardar cambios</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal detalles del cliente -->
    <div x-show="detalleAbierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
        <div @click.outside="detalleAbierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Datos del cliente</h3>
                <button @click="detalleAbierto = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-1 rounded-xl bg-gray-50 p-4 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Nombre</span><span class="font-semibold text-gray-900" x-text="detalle.nombre_completo"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Cédula</span><span class="font-semibold text-gray-900" x-text="detalle.cedula"></span></div>
                <template x-if="detalle.habitacion">
                    <div class="flex justify-between"><span class="text-gray-500">Habitación</span><span class="font-semibold text-gray-900" x-text="'Cuarto ' + detalle.habitacion"></span></div>
                </template>
                <template x-if="detalle.semana">
                    <div class="flex justify-between"><span class="text-gray-500">Semana</span><span class="font-semibold text-gray-900" x-text="detalle.semana"></span></div>
                </template>
            </div>

            <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Foto cédula</p>
            <div class="mt-2">
                <template x-if="detalle.foto_cedula_frontal || detalle.foto_cedula_trasera">
                    <a :href="detalle.foto_cedula_frontal || detalle.foto_cedula_trasera" target="_blank">
                        <img :src="detalle.foto_cedula_frontal || detalle.foto_cedula_trasera" class="w-full h-40 object-cover rounded-lg border border-gray-100">
                    </a>
                </template>
                <template x-if="!detalle.foto_cedula_frontal && !detalle.foto_cedula_trasera">
                    <div class="w-full h-40 rounded-lg border border-dashed border-gray-200 flex items-center justify-center text-xs text-gray-300">Sin foto</div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal ver foto(s) de cédula -->
    <div x-show="verFotos" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click="verFotos = null" style="display: none;">
        <div class="flex flex-wrap gap-4 items-center justify-center max-w-[95vw] max-h-[90vh]" @click.stop>
            <template x-for="foto in (verFotos || [])" :key="foto">
                <div class="min-w-0 shrink">
                    <img :src="foto" class="max-h-[75vh] max-w-[90vw] sm:max-w-[45vw] rounded-lg bg-white object-contain shadow-2xl">
                </div>
            </template>
        </div>
    </div>

    </div>
</x-app-layout>
