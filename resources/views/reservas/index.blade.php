<x-app-layout>
    <x-slot name="header">
        Reserva Habitación
    </x-slot>

    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            main { margin: 0 !important; }
        }
    </style>

    <div x-data="{
            abierto: false,
            habitacionId: null,
            habitacionNumero: '',
         }">

    @if (session('status'))
        <div class="no-print mb-6 px-4 py-3 rounded-xl bg-blue-50 text-blue-700 text-sm border border-blue-100">
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
                <div class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-fit">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
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
                                        <div class="relative aspect-square rounded-md border border-red-100 bg-red-50/60 p-0.5 flex flex-col items-center justify-center text-center cursor-pointer"
                                             @click="detalle = (detalle === {{ $habitacion->id }} ? null : {{ $habitacion->id }})">
                                            <span class="font-semibold text-gray-900 text-[9px] leading-tight">Cuarto {{ $habitacion->numero }}</span>
                                            <span class="text-[8px] text-gray-600 truncate w-full leading-tight">{{ \Illuminate\Support\Str::before($reserva->nombre, ' ') }}</span>

                                            <div x-show="detalle === {{ $habitacion->id }}" x-cloak @click.outside="detalle = null" @click.stop
                                                 class="absolute z-10 top-full left-1/2 -translate-x-1/2 mt-1 w-48 rounded-lg border border-gray-100 bg-white p-3 text-left shadow-lg cursor-default">
                                                <p class="text-sm font-medium text-gray-900">{{ $reserva->nombre }}</p>
                                                <p class="text-xs text-gray-500">CC {{ $reserva->cedula }} · {{ $reserva->telefono }}</p>
                                                @if ($reserva->deposito_estado)
                                                    <p class="text-xs text-gray-500 mt-1">Depósito: {{ $reserva->deposito_estado }}
                                                        @if ($reserva->prenda_id)
                                                            <span class="text-amber-600 font-medium">(pendiente)</span>
                                                        @endif
                                                    </p>
                                                @endif
                                                <form method="POST" action="{{ route('reservas.cancelar', $reserva) }}" class="mt-2" onsubmit="return confirm('¿Cancelar esta reserva?')">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Cancelar reserva</button>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <button type="button" @click="abierto = true; habitacionId = {{ $habitacion->id }}; habitacionNumero = '{{ $habitacion->numero }}'"
                                                class="aspect-square rounded-md border border-green-100 bg-green-50/60 flex items-center justify-center hover:bg-green-50 transition-colors p-0.5">
                                            <span class="font-semibold text-gray-900 text-[9px] leading-tight">Cuarto {{ $habitacion->numero }}</span>
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

            <!-- Clientes (imprimible) -->
            <div class="hidden print:block">
                <h2 class="font-semibold text-gray-900 mb-3">Clientes</h2>
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="text-left py-2 pr-3">Nombre completo</th>
                            <th class="text-left py-2 pr-3">Cédula</th>
                            <th class="text-left py-2 pr-3">Celular</th>
                            <th class="text-left py-2 pr-3">Habitación</th>
                            <th class="text-left py-2">Dejó prenda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientes as $cliente)
                            <tr class="border-b border-gray-200">
                                <td class="py-2 pr-3">{{ $cliente->nombre_completo }}</td>
                                <td class="py-2 pr-3">{{ $cliente->cedula }}</td>
                                <td class="py-2 pr-3">{{ $cliente->celular ?? '—' }}</td>
                                <td class="py-2 pr-3">{{ $habitacionPorCedula[$cliente->cedula] ?? '—' }}</td>
                                <td class="py-2">{{ $cedulasConPrenda->contains($cliente->cedula) ? 'Sí' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-400">Todavía no hay clientes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

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
                                <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Celular</th>
                                <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Cédula (fotos)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clientes as $cliente)
                                <tr class="border-b border-gray-50 last:border-0">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $cliente->nombre_completo }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ $cliente->cedula }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ $cliente->celular ?? '—' }}</td>
                                    <td class="px-6 py-4 text-xs">
                                        <div class="flex gap-2">
                                            @if ($cliente->foto_cedula_frontal)
                                                <a href="{{ asset('storage/' . $cliente->foto_cedula_frontal) }}" target="_blank" class="text-blue-700 hover:text-blue-800 font-medium">Frontal</a>
                                            @endif
                                            @if ($cliente->foto_cedula_trasera)
                                                <a href="{{ asset('storage/' . $cliente->foto_cedula_trasera) }}" target="_blank" class="text-blue-700 hover:text-blue-800 font-medium">Trasera</a>
                                            @endif
                                            @if (! $cliente->foto_cedula_frontal && ! $cliente->foto_cedula_trasera)
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">Todavía no hay clientes registrados.</td>
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
                    estado: 'pendiente',
                    editar(prenda) {
                        this.id = prenda.id;
                        this.descripcion = prenda.descripcion;
                        this.estado = prenda.estado;
                        this.abierto = true;
                    },
                 }"
                 class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-fit">

                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Prendas pendientes y devueltas</h2>
                </div>

                <ul class="divide-y divide-gray-50 max-h-[420px] overflow-y-auto">
                    @forelse ($prendas as $prenda)
                        <li class="px-6 py-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $prenda->descripcion }}</p>
                                <p class="text-xs text-gray-500">{{ $prenda->cliente ?? '—' }} · {{ $prenda->created_at->format('d/m/Y') }}
                                    <span class="ml-1 font-medium {{ $prenda->estado === 'devuelta' ? 'text-green-700' : 'text-amber-700' }}">
                                        {{ $prenda->estado === 'devuelta' ? 'Devuelta' : 'Pendiente' }}
                                    </span>
                                </p>
                            </div>
                            <button @click="editar({{ \Illuminate\Support\Js::from($prenda->only(['id', 'descripcion', 'estado'])) }})"
                                    class="shrink-0 text-xs font-medium text-blue-700 hover:text-blue-800">
                                Editar
                            </button>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-400">Todavía no hay prendas registradas.</li>
                    @endforelse
                </ul>

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
                                <select name="estado" x-model="estado" class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="devuelta">Devuelta</option>
                                </select>
                            </div>

                            <div x-show="estado === 'devuelta'">
                                <x-input-label for="foto" value="Foto de la prenda devuelta" />
                                <input id="foto" name="foto" type="file" accept="image/*"
                                       class="mt-1.5 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-sm file:font-medium">
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
                    <x-input-label for="telefono" value="Teléfono" />
                    <x-text-input id="telefono" name="telefono" type="text" class="mt-1.5" required :value="old('telefono')" />
                </div>

                <div>
                    <x-input-label for="deposito_estado" value="Depósito / prenda dejada" />
                    <x-text-input id="deposito_estado" name="deposito_estado" type="text" class="mt-1.5" placeholder="Ej. Cédula, reloj, o 'No' si no dejó nada" :value="old('deposito_estado')" />
                    <p class="mt-1 text-xs text-gray-400">Si escribís algo distinto de "No" o lo dejás vacío se crea una alerta de depósito pendiente.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="foto_cedula" value="Foto de cédula" />
                        <input id="foto_cedula" name="foto_cedula" type="file" accept="image/*"
                               class="mt-1.5 block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-xs file:font-medium">
                    </div>
                    <div>
                        <x-input-label for="foto_deposito" value="Foto del depósito" />
                        <input id="foto_deposito" name="foto_deposito" type="file" accept="image/*"
                               class="mt-1.5 block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-xs file:font-medium">
                    </div>
                </div>

                <div>
                    <x-input-label for="observaciones" value="Observaciones" />
                    <textarea id="observaciones" name="observaciones" rows="2"
                              class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700">{{ old('observaciones') }}</textarea>
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

    </div>
</x-app-layout>
