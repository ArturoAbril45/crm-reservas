<x-app-layout>
    <x-slot name="header">
        Caja
    </x-slot>

    <div x-data="{ abierto: false, tipo: 'INGRESO' }">

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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Total del día</p>
            <p class="text-2xl font-semibold {{ $total >= 0 ? 'text-gray-900' : 'text-red-600' }}">$ {{ number_format($total, 2) }}</p>
        </div>
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between gap-4">
            <form method="GET" action="{{ route('caja') }}" class="flex items-center gap-3">
                <x-input-label for="fecha" value="Fecha" class="shrink-0" />
                <input id="fecha" name="fecha" type="date" value="{{ $fecha }}" onchange="this.form.submit()"
                       class="block rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
            </form>
            @if ($sucursal)
                <button @click="abierto = true" class="inline-flex items-center gap-2 rounded-lg bg-[#9c0720] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#7c0519]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nuevo movimiento
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Concepto</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $mov)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-4">
                                <span @class([
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                                    'bg-green-50 text-green-700' => $mov->tipo === 'INGRESO',
                                    'bg-red-50 text-red-600' => $mov->tipo === 'EGRESO',
                                    'bg-gray-100 text-gray-500' => in_array($mov->tipo, ['APERTURA', 'CIERRE']),
                                ])>{{ $mov->tipo }}</span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $mov->concepto }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $mov->usuario->name ?? '—' }}</td>
                            <td class="px-6 py-4 font-medium {{ $mov->tipo === 'EGRESO' ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $mov->tipo === 'EGRESO' ? '-' : '' }}$ {{ number_format($mov->monto, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-400">No hay movimientos de caja en esta fecha.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal nuevo movimiento -->
    <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
        <div @click.outside="abierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Nuevo movimiento de caja</h3>
                <button @click="abierto = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('caja.store') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label value="Tipo" class="mb-1.5" />
                    <input type="hidden" name="tipo" x-model="tipo">
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="t in ['INGRESO', 'EGRESO', 'APERTURA', 'CIERRE']" :key="t">
                            <button type="button" @click="tipo = t"
                                    class="rounded-lg border py-2 text-xs font-medium transition-colors"
                                    :class="tipo === t ? 'border-[#9c0720] text-[#9c0720] bg-[#9c0720]/10' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                    x-text="t"></button>
                        </template>
                    </div>
                </div>

                <div>
                    <x-input-label for="concepto" value="Concepto" />
                    <x-text-input id="concepto" name="concepto" type="text" class="mt-1.5" required :value="old('concepto')" />
                </div>

                <div>
                    <x-input-label for="monto" value="Monto ($)" />
                    <x-text-input id="monto" name="monto" type="number" min="0.01" step="0.01" class="mt-1.5" required :value="old('monto')" />
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="abierto = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <x-primary-button class="flex-1 justify-center">Guardar</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    </div>
</x-app-layout>
