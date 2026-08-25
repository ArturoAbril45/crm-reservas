@php
    $navItems = collect([
        ['route' => 'reservas', 'permiso' => 'reservas', 'label' => 'Reserva Habitación', 'icon' => 'calendar'],
        ['route' => 'almacenes', 'permiso' => 'almacenes', 'label' => 'Almacenes', 'icon' => 'archive'],
        ['route' => 'productos', 'permiso' => 'productos', 'label' => 'Productos', 'icon' => 'box'],
        ['route' => 'ventas', 'permiso' => 'ventas', 'label' => 'Venta', 'icon' => 'tag'],
        ['route' => 'traspasos', 'permiso' => 'traspasos', 'label' => 'Traspasos', 'icon' => 'swap'],
        ['route' => 'compras', 'permiso' => 'compras', 'label' => 'Compras', 'icon' => 'cart'],
        ['route' => 'gastos', 'permiso' => 'gastos', 'label' => 'Gastos', 'icon' => 'receipt'],
        ['route' => 'caja', 'permiso' => 'caja', 'label' => 'Caja', 'icon' => 'wallet'],
        ['route' => 'sucursales.index', 'permiso' => 'sucursales.index', 'label' => 'Sucursales', 'icon' => 'building'],
        ['route' => 'reporte-ventas', 'permiso' => 'reporte-ventas', 'label' => 'Reportes', 'icon' => 'chart'],
        ['route' => 'roles', 'permiso' => 'roles', 'label' => 'Roles', 'icon' => 'shield'],
    ])->filter(fn ($item) => auth()->user()->puedeVer($item['permiso']));

    $sucursales = \App\Models\Sucursal::orderBy('nombre')->get();
    $sucursalActualId = session('sucursal_actual_id') ?? optional($sucursales->first())->id;
    $sucursalActual = $sucursales->firstWhere('id', $sucursalActualId) ?? $sucursales->first();
@endphp

<!-- Nav flotante -->
<aside class="hidden lg:flex lg:flex-col fixed top-4 left-4 bottom-4 w-60 z-40 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">

    <!-- Selector de sucursal -->
    <div x-data="{ open: false, loading: false }" class="relative border-b border-gray-100 shrink-0">
        <button @click="open = !open" class="w-full flex items-center gap-3 h-16 px-5 hover:bg-gray-50 transition-colors">
            <x-nav-icon name="store" class="h-6 w-6 text-blue-700 shrink-0" />
            <span class="flex-1 text-left min-w-0">
                <span class="block text-[10px] font-medium uppercase tracking-wider text-gray-400">Sucursal</span>
                <span class="block font-semibold text-gray-900 truncate">{{ $sucursalActual->nombre ?? 'Sin sucursal' }}</span>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-gray-400 shrink-0" :class="{ 'rotate-180': open }">
                <path d="m6 9 6 6 6-6" />
            </svg>
        </button>

        <div x-show="open" @click.outside="open = false" x-cloak
             class="absolute left-3 right-3 top-[calc(100%-8px)] z-50 bg-white border border-gray-100 rounded-xl shadow-lg overflow-hidden">
            @foreach ($sucursales as $s)
                <form method="POST" action="{{ route('sucursales.cambiar', $s) }}" @submit="loading = true">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 {{ $s->id === optional($sucursalActual)->id ? 'text-blue-700 font-medium bg-blue-50' : 'text-gray-700' }}">
                        {{ $s->nombre }}
                        @if ($s->id === optional($sucursalActual)->id)
                            <x-nav-icon name="check" class="h-4 w-4 text-blue-700" />
                        @endif
                    </button>
                </form>
            @endforeach
            <a href="{{ route('sucursales.index') }}" class="block px-4 py-2.5 text-sm text-gray-500 border-t border-gray-100 hover:bg-gray-50">
                Administrar sucursales
            </a>
        </div>

        <!-- Overlay de carga mientras cambia de sucursal -->
        <div x-show="loading" x-cloak class="fixed inset-0 z-[60] bg-white/70 flex items-center justify-center">
            <svg class="animate-spin h-6 w-6 text-blue-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
        @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <x-nav-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-blue-700' : 'text-gray-400' }}" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="p-3 border-t border-gray-100">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); this.closest('form').submit();"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900 cursor-pointer">
                <x-nav-icon name="logout" class="h-[18px] w-[18px] shrink-0 text-gray-400" />
                {{ __('Cerrar sesión') }}
            </a>
        </form>
    </div>
</aside>

<!-- Nav móvil -->
<nav x-data="{ open: false }" class="lg:hidden bg-white border-b border-gray-100 relative z-40">
    <div class="px-4">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center gap-2.5 min-w-0">
                <x-nav-icon name="store" class="h-6 w-6 text-blue-700 shrink-0" />
                <span class="font-semibold text-gray-900 truncate">{{ $sucursalActual->nombre ?? 'Sin sucursal' }}</span>
            </div>

            <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-gray-100">
        <div class="px-3 py-3 space-y-0.5">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $active ? 'bg-blue-50 text-blue-700' : 'text-gray-600' }}">
                    <x-nav-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-blue-700' : 'text-gray-400' }}" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
        <div class="border-t border-gray-100 px-3 py-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-500">
                    <x-nav-icon name="logout" class="h-[18px] w-[18px] shrink-0 text-gray-400" />
                    {{ __('Cerrar sesión') }}
                </a>
            </form>
        </div>
    </div>
</nav>
