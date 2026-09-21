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
        ['route' => 'whatsapp', 'permiso' => 'whatsapp', 'label' => 'WhatsApp API', 'icon' => 'chat'],
        ['route' => 'whatsapp.solicitudes', 'permiso' => 'solicitudes', 'label' => 'Solicitudes', 'icon' => 'calendar'],
        ['route' => 'flujos', 'permiso' => 'flujos', 'label' => 'Conexión de flujos', 'icon' => 'flujo'],
        ['route' => 'roles', 'permiso' => 'roles', 'label' => 'Roles', 'icon' => 'shield'],
    ])->filter(fn ($item) => auth()->user()->puedeVer($item['permiso']));

    $sucursales = \App\Models\Sucursal::orderBy('nombre')->get();
    $sucursalActualId = session('sucursal_actual_id') ?? optional($sucursales->first())->id;
    $sucursalActual = $sucursales->firstWhere('id', $sucursalActualId) ?? $sucursales->first();
@endphp

<!-- Nav flotante -->
<aside class="no-print hidden lg:flex lg:flex-col fixed top-4 left-4 bottom-4 w-60 z-40 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">

    <!-- Reloj (hora de Ecuador) -->
    <x-reloj-ecuador class="border-b border-gray-100 shrink-0 py-3 px-5" />

    <!-- Sucursal actual (fija: se elige una sola vez al loguearse, no se cambia desde acá) -->
    <div class="flex items-center gap-3 h-16 px-5 border-b border-gray-100 shrink-0">
        <x-nav-icon name="store" class="h-6 w-6 text-[#9c0720] shrink-0" />
        <span class="flex-1 min-w-0">
            <span class="block text-[10px] font-medium uppercase tracking-wider text-gray-400">Sucursal</span>
            <span class="block font-semibold text-gray-900 truncate">{{ $sucursalActual->nombre ?? 'Sin sucursal' }}</span>
        </span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
        @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-[#9c0720]/10 text-[#9c0720]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <x-nav-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-[#9c0720]' : 'text-gray-400' }}" />
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
<nav x-data="{ open: false }" class="no-print lg:hidden bg-white border-b border-gray-100 relative z-40">
    <x-reloj-ecuador class="border-b border-gray-100 py-2" />
    <div class="px-4">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center gap-2.5 min-w-0">
                <x-nav-icon name="store" class="h-6 w-6 text-[#9c0720] shrink-0" />
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
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $active ? 'bg-[#9c0720]/10 text-[#9c0720]' : 'text-gray-600' }}">
                    <x-nav-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-[#9c0720]' : 'text-gray-400' }}" />
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
