<x-app-layout>
    <x-slot name="header">
        Gastos
    </x-slot>

    <div x-data="{ formaPago: 'Efectivo' }">

    @if (session('status'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-[#9c0720]/10 text-[#9c0720] text-sm border border-[#9c0720]/15">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Lista -->
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-fit">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Últimos gastos</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Categoría</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Pago</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Monto</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($gastos as $gasto)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $gasto->categoria }}
                                    @if ($gasto->es_general)
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">General</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $gasto->descripcion ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $gasto->forma_pago }}</td>
                                <td class="px-6 py-4 text-gray-900 font-medium">$ {{ number_format($gasto->monto, 2) }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $gasto->fecha->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">Todavía no hay gastos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Formulario -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
            <h2 class="font-semibold text-gray-900 mb-4">Registrar gasto</h2>

            @if ($sucursales->isEmpty())
                <p class="text-sm text-gray-500">Primero creá una sucursal.</p>
            @else
                <form method="POST" action="{{ route('gastos.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="sucursal_id" value="Sucursal" />
                        <select id="sucursal_id" name="sucursal_id" required
                                class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                            <option value="" disabled selected>Seleccioná una sucursal</option>
                            @foreach ($sucursales as $s)
                                <option value="{{ $s->id }}" @selected(optional($sucursal)->id === $s->id)>{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="categoria" value="Categoría" />
                        <select id="categoria" name="categoria" class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria }}">{{ $categoria }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción" />
                        <x-text-input id="descripcion" name="descripcion" type="text" class="mt-1.5" :value="old('descripcion')" />
                    </div>

                    <div>
                        <x-input-label for="monto" value="Monto ($)" />
                        <x-text-input id="monto" name="monto" type="number" min="0.01" step="0.01" class="mt-1.5" required :value="old('monto')" />
                    </div>

                    <div>
                        <x-input-label value="Forma de pago" class="mb-1.5" />
                        <input type="hidden" name="forma_pago" x-model="formaPago">
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="fp in ['Efectivo', 'Transferencia']" :key="fp">
                                <button type="button" @click="formaPago = fp"
                                        class="rounded-lg border py-2 text-xs font-medium transition-colors"
                                        :class="formaPago === fp ? 'border-[#9c0720] text-[#9c0720] bg-[#9c0720]/10' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                        x-text="fp"></button>
                            </template>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400" x-show="formaPago === 'Efectivo'">
                        Este gasto va a generar un egreso automático en Caja de la sucursal elegida.
                    </p>

                    <x-primary-button>Registrar gasto</x-primary-button>
                </form>
            @endif
        </div>
    </div>

    </div>
</x-app-layout>
