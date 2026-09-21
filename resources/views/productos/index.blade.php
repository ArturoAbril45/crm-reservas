<x-app-layout>
    <x-slot name="header">
        Productos
    </x-slot>

    <datalist id="unidades-sugeridas">
        <option value="Unidad"></option>
        <option value="Docena"></option>
        <option value="Caja"></option>
        <option value="Six pack"></option>
        <option value="Jaba de 12"></option>
        <option value="Jaba de 24"></option>
        <option value="Paca"></option>
        <option value="Servicio"></option>
    </datalist>

    @if ($almacenes->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500">
            Todavía no hay almacenes creados. <a href="{{ route('almacenes') }}" class="text-[#9c0720] font-medium hover:text-[#7c0519]">Creá uno primero</a>.
        </div>
    @else
        <div x-data="{
                abierto: null,
                abiertoPrecio: null,
                tipoItem: '{{ old('tipo_item', 'Producto') }}',
                editando: null,
                editForm: { codigo: '', nombre: '', unidad: '', tipo_item: 'Producto', costo: 0, precio: 0, stock_minimo: 0, stock_minimo_presentacion: '', presentaciones: [] },
                editar(p) {
                    this.editando = p.id;
                    this.editForm = {
                        codigo: p.codigo, nombre: p.nombre, unidad: p.unidad, tipo_item: p.tipo_item,
                        costo: p.costo, precio: p.precio,
                        stock_minimo: p.stock_minimo, stock_minimo_presentacion: p.stock_minimo_presentacion || '',
                        presentaciones: p.presentaciones ?? [],
                    };
                },
             }">
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

            <div class="mb-6 flex items-center justify-between gap-3 flex-wrap">
                @if ($almacenes->count() > 1)
                    <form method="GET" action="{{ route('productos') }}" class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700">Ver stock y precio de</label>
                        <select name="almacen" onchange="this.form.submit()"
                                class="rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                            @foreach ($almacenes as $a)
                                <option value="{{ $a->id }}" @selected($almacenActual && $almacenActual->id === $a->id)>{{ $a->nombre }} — {{ $a->sucursal->nombre ?? '—' }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span></span>
                @endif

                <a href="{{ route('productos.carga-inicial', $almacenActual ? ['almacen' => $almacenActual->id] : []) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M20 12H4M12 4v16" />
                    </svg>
                    Carga inicial
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Lista -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Catálogo — stock y precio en</p>
                        <p class="font-semibold text-gray-900">{{ $almacenActual->nombre ?? '—' }} @if($almacenActual) <span class="font-normal text-gray-400">({{ $almacenActual->sucursal->nombre ?? '—' }})</span>@endif</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Unidad</th>
                                    <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Stock acá</th>
                                    <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Costo</th>
                                    <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Precio acá</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($productos as $producto)
                                    @php
                                        $precioAqui = $producto->precioEnAlmacen(optional($almacenActual)->id);
                                        $stockAqui = $producto->stockEnAlmacen(optional($almacenActual)->id);
                                    @endphp
                                    <tr class="border-b border-gray-50">
                                        <td class="px-6 py-4 text-gray-500">{{ $producto->codigo ?? '—' }}</td>
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            {{ $producto->nombre }}
                                            @if ($producto->tipo_item === 'Servicio')
                                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-50 text-purple-700">Servicio</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ $producto->unidad }}</td>
                                        <td class="px-6 py-4">
                                            @if ($producto->controla_stock)
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $producto->bajoStockMinimo(optional($almacenActual)->id) ? 'bg-amber-50 text-amber-700' : ($stockAqui > 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600') }}">
                                                        {{ $stockAqui }}
                                                    </span>
                                                    @if ($producto->stock_minimo > 0)
                                                        <span class="text-[10px] text-gray-400" title="Stock mínimo configurado">
                                                            mín. {{ rtrim(rtrim($producto->stock_minimo, '0'), '.') }} {{ $producto->stock_minimo_presentacion ?? $producto->unidad }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-300">Sin stock</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">$ {{ number_format($producto->costo, 2) }}</td>
                                        <td class="px-6 py-4 font-medium text-gray-900">$ {{ number_format($precioAqui, 2) }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap relative">
                                            @php
                                                $editData = $producto->only(['id', 'codigo', 'nombre', 'unidad', 'tipo_item', 'costo', 'precio', 'stock_minimo', 'stock_minimo_presentacion']);
                                                $editData['presentaciones'] = $producto->presentaciones->where('activo', true)->pluck('nombre')->values();
                                            @endphp
                                            <div x-data="{ menu: false }" @click.outside="menu = false" class="inline-block text-left">
                                                <button @click="menu = !menu" type="button" class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                                        <circle cx="12" cy="5" r="1.5" /><circle cx="12" cy="12" r="1.5" /><circle cx="12" cy="19" r="1.5" />
                                                    </svg>
                                                </button>
                                                <div x-show="menu" x-cloak x-transition class="absolute right-0 z-20 mt-1 w-44 rounded-lg border border-gray-100 bg-white shadow-lg py-1 text-left">
                                                    <button @click="menu = false; editar({{ \Illuminate\Support\Js::from($editData) }})" class="block w-full px-3.5 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                                        Editar
                                                    </button>
                                                    <button @click="menu = false; abierto = (abierto === {{ $producto->id }} ? null : {{ $producto->id }})" class="block w-full px-3.5 py-2 text-xs font-medium text-[#9c0720] hover:bg-gray-50">
                                                        Presentaciones
                                                    </button>
                                                    <button @click="menu = false; abiertoPrecio = (abiertoPrecio === {{ $producto->id }} ? null : {{ $producto->id }})" class="block w-full px-3.5 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                                        Precios por local
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr x-show="abiertoPrecio === {{ $producto->id }}" x-cloak class="border-b border-gray-50 bg-gray-50/60">
                                        <td colspan="7" class="px-6 py-4">
                                            <p class="text-[11px] text-gray-400 uppercase tracking-wider mb-3">Precios por local (código {{ $producto->codigo }}) — dejá vacío para usar el precio general</p>
                                            <form method="POST" action="{{ route('productos.precios-locales', $producto) }}" class="space-y-3">
                                                @csrf
                                                @foreach ($almacenes as $local)
                                                    @php
                                                        $generalLocal = $producto->preciosLocales->firstWhere('almacen_id', $local->id);
                                                    @endphp
                                                    <div class="rounded-lg border border-gray-200 p-3">
                                                        <div class="flex items-center gap-3 mb-2">
                                                            <span class="w-40 shrink-0 text-xs font-medium text-gray-700">
                                                                {{ $local->nombre }} <span class="text-gray-400">({{ $local->sucursal->nombre ?? '—' }})</span>
                                                            </span>
                                                            <span class="text-xs text-gray-400">Precio general de respaldo</span>
                                                            <span class="text-xs text-gray-400">$</span>
                                                            <input type="number" name="precio_local_{{ $local->id }}" value="{{ $generalLocal?->precio }}" min="0" step="0.01" placeholder="{{ number_format($producto->precio, 2) }}"
                                                                   class="w-24 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                        </div>
                                                        <div class="flex flex-wrap gap-3 pl-0">
                                                            @foreach ($producto->presentaciones as $presentacion)
                                                                @php
                                                                    $overridePresentacion = $producto->presentacionPreciosLocales
                                                                        ->first(fn ($pl) => $pl->almacen_id === $local->id && $pl->presentacion === $presentacion->nombre);
                                                                @endphp
                                                                <div class="flex items-center gap-1.5">
                                                                    <span class="text-[11px] text-gray-500">{{ $presentacion->nombre }}</span>
                                                                    <span class="text-xs text-gray-400">$</span>
                                                                    <input type="number" name="precio_local_{{ $local->id }}_presentacion_{{ bin2hex($presentacion->nombre) }}" value="{{ $overridePresentacion?->precio }}" min="0" step="0.01" placeholder="{{ number_format($presentacion->precio ?? $producto->precio, 2) }}"
                                                                           class="w-20 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                                <button type="submit" class="rounded-lg bg-[#9c0720] px-3.5 py-2 text-xs font-medium text-white hover:bg-[#7c0519]">
                                                    Guardar precios por local
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr x-show="abierto === {{ $producto->id }}" x-cloak class="border-b border-gray-50 bg-gray-50/60">
                                        <td colspan="7" class="px-6 py-4">
                                            <div class="space-y-2 mb-3">
                                                @forelse ($producto->presentaciones as $presentacion)
                                                    <div class="flex flex-wrap items-end gap-2 {{ ! $presentacion->activo ? 'opacity-50' : '' }}">
                                                        <span class="w-28 shrink-0 text-xs font-medium text-gray-700 pb-2">{{ $presentacion->nombre }}</span>
                                                        <form method="POST" action="{{ route('productos.presentaciones.update', [$producto, $presentacion]) }}" class="flex flex-wrap items-end gap-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <div>
                                                                <label class="block text-[11px] text-gray-400 mb-1">Contiene</label>
                                                                <input type="number" name="factor" value="{{ $presentacion->factor }}" required min="0.0001" step="0.0001"
                                                                       class="w-20 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                            </div>
                                                            <div>
                                                                <label class="block text-[11px] text-gray-400 mb-1">Costo ($)</label>
                                                                <input type="number" name="costo" value="{{ $presentacion->costo }}" min="0" step="0.01"
                                                                       class="w-20 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                            </div>
                                                            <div>
                                                                <label class="block text-[11px] text-gray-400 mb-1">Precio ($)</label>
                                                                <input type="number" name="precio" value="{{ $presentacion->precio }}" min="0" step="0.01"
                                                                       class="w-20 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                            </div>
                                                            <button type="submit" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                                                Guardar
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('productos.presentaciones.activar', [$producto, $presentacion]) }}">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium {{ $presentacion->activo ? 'bg-[#9c0720]/10 text-[#9c0720] hover:bg-[#9c0720]/15' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                                                {{ $presentacion->activo ? 'Habilitada' : 'Deshabilitada' }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                @empty
                                                    <p class="text-xs text-gray-400">Sin presentaciones todavía.</p>
                                                @endforelse
                                            </div>

                                            <form method="POST" action="{{ route('productos.presentaciones.store', $producto) }}" class="flex flex-wrap items-end gap-2">
                                                @csrf
                                                <div>
                                                    <label class="block text-[11px] text-gray-400 mb-1">Nombre</label>
                                                    <input type="text" name="nombre" required placeholder="Jaba de 12"
                                                           class="w-32 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] text-gray-400 mb-1">Unidades que contiene</label>
                                                    <input type="number" name="factor" required min="0.0001" step="0.0001" value="12"
                                                           class="w-24 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] text-gray-400 mb-1">Costo ($)</label>
                                                    <input type="number" name="costo" min="0" step="0.01"
                                                           class="w-24 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] text-gray-400 mb-1">Precio ($)</label>
                                                    <input type="number" name="precio" min="0" step="0.01"
                                                           class="w-24 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                </div>
                                                <button type="submit" class="rounded-lg bg-[#9c0720] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#7c0519]">
                                                    Agregar
                                                </button>
                                            </form>
                                            @error('nombre')
                                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-gray-400">Todavía no hay productos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
                    <h2 class="font-semibold text-gray-900 mb-4">Nuevo producto</h2>

                    <form method="POST" action="{{ route('productos.store') }}" class="space-y-4">
                        @csrf

                        @if ($almacenes->count() > 1)
                            <div>
                                <x-input-label for="almacen_id" value="Almacén (para el stock inicial)" />
                                <select id="almacen_id" name="almacen_id" class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                                    @foreach ($almacenes as $a)
                                        <option value="{{ $a->id }}" @selected($almacenActual && $almacenActual->id === $a->id)>{{ $a->nombre }} — {{ $a->sucursal->nombre ?? '—' }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('almacen_id')" class="mt-2" />
                            </div>
                        @else
                            <input type="hidden" name="almacen_id" value="{{ optional($almacenActual)->id }}">
                        @endif

                        <div>
                            <x-input-label value="Tipo" class="mb-1.5" />
                            <input type="hidden" name="tipo_item" x-model="tipoItem">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($tipos as $tipo)
                                    <button type="button" @click="tipoItem = '{{ $tipo }}'"
                                            class="rounded-lg border py-2 text-xs font-medium transition-colors"
                                            :class="tipoItem === '{{ $tipo }}' ? 'border-[#9c0720] text-[#9c0720] bg-[#9c0720]/10' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                                        {{ $tipo }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400" x-show="tipoItem === 'Servicio'">Un servicio no controla stock (ej. un cargo fijo, una comisión).</p>
                        </div>

                        <div>
                            <x-input-label for="codigo" value="Código" />
                            <x-text-input id="codigo" name="codigo" type="text" class="mt-1.5" required :value="old('codigo')" />
                            <p class="mt-1.5 text-xs text-gray-400">El producto es único por código en todo el sistema (no se duplica por sucursal).</p>
                            <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="nombre" value="Nombre" />
                            <x-text-input id="nombre" name="nombre" type="text" class="mt-1.5" required :value="old('nombre')" placeholder="Ej. Cerveza Ecuador" />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="unidad" value="Presentación principal" />
                            <input id="unidad" name="unidad" type="text" list="unidades-sugeridas" required value="{{ old('unidad') }}" placeholder="Ej. Unidad, Jaba de 12, Six pack..."
                                   class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                            <x-input-error :messages="$errors->get('unidad')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="costo" value="Costo ($)" />
                                <x-text-input id="costo" name="costo" type="number" min="0" step="0.01" class="mt-1.5" required :value="old('costo', 0)" />
                                <x-input-error :messages="$errors->get('costo')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="precio" value="Precio público ($)" />
                                <x-text-input id="precio" name="precio" type="number" min="0" step="0.01" class="mt-1.5" required :value="old('precio', 0)" />
                                <x-input-error :messages="$errors->get('precio')" class="mt-2" />
                            </div>
                        </div>

                        <div x-show="tipoItem !== 'Servicio'" class="space-y-4">
                            <div>
                                <x-input-label for="stock" value="Stock inicial (en el almacén elegido arriba)" />
                                <x-text-input id="stock" name="stock" type="number" min="0" class="mt-1.5" :value="old('stock', 0)" />
                                <x-input-error :messages="$errors->get('stock')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="stock_minimo" value="Stock mínimo (alerta)" />
                                <x-text-input id="stock_minimo" name="stock_minimo" type="number" min="0" step="0.01" class="mt-1.5" :value="old('stock_minimo', 0)" />
                                <p class="mt-1.5 text-xs text-gray-400">Expresado en la presentación principal. Podés cambiarlo a otra presentación después, editando el producto.</p>
                            </div>
                        </div>

                        <x-primary-button>Agregar producto</x-primary-button>
                    </form>
                </div>
            </div>

            <!-- Modal editar producto -->
            <div x-show="editando" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8 overflow-y-auto">
                <div @click.outside="editando = null" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 my-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900">Editar producto</h3>
                        <button @click="editando = null" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <template x-if="editando">
                        <form method="POST" :action="'{{ url('/productos') }}/' + editando" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="almacen_id" value="{{ optional($almacenActual)->id }}">
                            <input type="hidden" name="tipo_item" x-model="editForm.tipo_item">
                            <input type="hidden" name="codigo" x-model="editForm.codigo">

                            <div>
                                <x-input-label value="Tipo" class="mb-1.5" />
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($tipos as $tipo)
                                        <button type="button" @click="editForm.tipo_item = '{{ $tipo }}'"
                                                class="rounded-lg border py-2 text-xs font-medium transition-colors"
                                                :class="editForm.tipo_item === '{{ $tipo }}' ? 'border-[#9c0720] text-[#9c0720] bg-[#9c0720]/10' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                                            {{ $tipo }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <x-input-label value="Nombre" />
                                <input type="text" name="nombre" x-model="editForm.nombre" required class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm">
                            </div>

                            <div>
                                <x-input-label value="Presentación principal" />
                                <input type="text" name="unidad" list="unidades-sugeridas" x-model="editForm.unidad" required class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <x-input-label value="Costo ($)" />
                                    <input type="number" name="costo" min="0" step="0.01" x-model="editForm.costo" required class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm">
                                </div>
                                <div>
                                    <x-input-label value="Precio público ($)" />
                                    <input type="number" name="precio" min="0" step="0.01" x-model="editForm.precio" required class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm">
                                </div>
                            </div>

                            <div x-show="editForm.tipo_item !== 'Servicio'" class="grid grid-cols-2 gap-3">
                                <div>
                                    <x-input-label value="Stock mínimo" />
                                    <input type="number" name="stock_minimo" min="0" step="0.01" x-model="editForm.stock_minimo" class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm">
                                </div>
                                <div>
                                    <x-input-label value="Presentación de alerta" />
                                    <select name="stock_minimo_presentacion" x-model="editForm.stock_minimo_presentacion" class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm">
                                        <option value="">— (usa la principal)</option>
                                        <template x-for="nombre in editForm.presentaciones" :key="nombre">
                                            <option :value="nombre" x-text="nombre"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400" x-show="editForm.tipo_item === 'Servicio'">Al pasar a Servicio, el stock queda en 0 y deja de controlarse.</p>

                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="editando = null" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancelar</button>
                                <x-primary-button class="flex-1 justify-center">Guardar</x-primary-button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>

        </div>
    @endif
</x-app-layout>
