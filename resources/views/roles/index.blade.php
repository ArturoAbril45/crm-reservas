<x-app-layout>
    <x-slot name="header">
        Roles
    </x-slot>

    @php
        $diasSemana = [0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb'];
    @endphp

    <div x-data="{ abierto: {{ $errors->any() ? 'true' : 'false' }}, editando: null }">

    @if (session('status'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-blue-50 text-blue-700 text-sm border border-blue-100">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex justify-end mb-4">
        <button @click="abierto = true" class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Nuevo usuario
        </button>
    </div>

        <!-- Lista -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Módulos</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Horario</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900">{{ $usuario->name }}</p>
                                <p class="text-xs text-gray-400">{{ $usuario->email }}</p>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $usuario->username ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                    {{ $usuario->role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                {{ collect($usuario->permisos)->map(fn ($m) => $modulos[$m] ?? $m)->implode(', ') ?: 'Ninguno' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                @if ($usuario->bloqueado_fecha?->isToday())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-50 text-red-600">Bloqueado hoy</span>
                                @elseif ($usuario->horario_activo)
                                    {{ $usuario->hora_inicio }}–{{ $usuario->hora_fin }}
                                @else
                                    Sin restricción
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="editando = {{ $usuario->id }}" class="text-sm font-medium text-blue-700 hover:text-blue-800">
                                    Editar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">Todavía no hay usuarios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    <!-- Modal nuevo usuario -->
    <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8 overflow-y-auto" style="display: none;">
        <div @click.outside="abierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-3xl p-6 my-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Nuevo usuario</h3>
                <button @click="abierto = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('roles.store') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" value="Nombre" />
                        <x-text-input id="name" name="name" type="text" class="mt-1.5" required :value="old('name')" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="username" value="Usuario" />
                        <x-text-input id="username" name="username" type="text" class="mt-1.5" required :value="old('username')" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Correo" />
                        <x-text-input id="email" name="email" type="email" class="mt-1.5" required :value="old('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Contraseña" />
                        <x-text-input id="password" name="password" type="password" class="mt-1.5" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="role" value="Rol" />
                        <x-text-input id="role" name="role" type="text" class="mt-1.5" required :value="old('role')" placeholder="Ej. Vendedor, Encargado de almacén..." />
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Módulos que puede ver" class="mb-2" />
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 rounded-lg border border-gray-200 p-3">
                        @foreach ($modulos as $key => $label)
                            <label class="flex items-center justify-between gap-2 px-2 py-1.5 cursor-pointer">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                                <span class="relative inline-flex items-center shrink-0">
                                    <input type="checkbox" name="permisos[]" value="{{ $key }}"
                                           {{ in_array($key, old('permisos', [])) ? 'checked' : '' }}
                                           class="peer sr-only">
                                    <span class="h-5 w-9 rounded-full bg-gray-200 peer-checked:bg-blue-700 transition-colors"></span>
                                    <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"></span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('permisos')" class="mt-2" />
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <x-input-label value="Restricción de horario de acceso" />
                        <label class="relative inline-flex items-center shrink-0 cursor-pointer">
                            <input type="checkbox" name="horario_activo" value="1" {{ old('horario_activo') ? 'checked' : '' }} class="peer sr-only">
                            <span class="h-5 w-9 rounded-full bg-gray-200 peer-checked:bg-blue-700 transition-colors"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"></span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-400">Fuera de la hora y los días marcados, no va a poder entrar con su usuario y contraseña.</p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="hora_inicio" value="Hora de entrada" class="text-xs" />
                            <input type="time" id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio', '08:00') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <x-input-error :messages="$errors->get('hora_inicio')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="hora_fin" value="Hora máxima" class="text-xs" />
                            <input type="time" id="hora_fin" name="hora_fin" value="{{ old('hora_fin', '18:00') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <x-input-error :messages="$errors->get('hora_fin')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Días de acceso" class="text-xs mb-1" />
                        <div class="flex flex-wrap gap-2">
                            @foreach ($diasSemana as $numero => $label)
                                <label class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-gray-200 text-xs text-gray-600 cursor-pointer has-checked:bg-blue-50 has-checked:border-blue-200 has-checked:text-blue-700">
                                    <input type="checkbox" name="dias_trabajo[]" value="{{ $numero }}" {{ in_array($numero, old('dias_trabajo', [1,2,3,4,5,6])) ? 'checked' : '' }} class="h-3.5 w-3.5">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('dias_trabajo')" class="mt-2" />
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="abierto = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <x-primary-button class="flex-1 justify-center">Crear usuario</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modales de edición por usuario -->
    @foreach ($usuarios as $usuario)
        <div x-show="editando === {{ $usuario->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8 overflow-y-auto" style="display: none;">
            <div @click.outside="editando = null" class="bg-white rounded-2xl shadow-xl w-full max-w-5xl p-6 my-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-semibold text-gray-900">Editar {{ $usuario->name }}</h3>
                    <button @click="editando = null" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Permisos -->
                    <form method="POST" action="{{ route('roles.permisos', $usuario) }}" class="space-y-3">
                        @csrf
                        <x-input-label value="Módulos que puede ver" />
                        <div class="grid grid-cols-2 gap-2 rounded-lg border border-gray-200 p-3">
                            @foreach ($modulos as $key => $label)
                                <label class="flex items-center justify-between gap-2 px-2 py-1.5 cursor-pointer">
                                    <span class="text-sm text-gray-700">{{ $label }}</span>
                                    <span class="relative inline-flex items-center shrink-0">
                                        <input type="checkbox" name="permisos[]" value="{{ $key }}"
                                               {{ in_array($key, $usuario->permisos ?? []) ? 'checked' : '' }}
                                               class="peer sr-only">
                                        <span class="h-5 w-9 rounded-full bg-gray-200 peer-checked:bg-blue-700 transition-colors"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full py-2 rounded-lg bg-blue-700 text-white text-sm font-medium hover:bg-blue-800">
                            Guardar módulos
                        </button>
                    </form>

                    <!-- Horario y bloqueo -->
                    <div class="space-y-5">
                        @if ($usuario->id !== auth()->id())
                            <form method="POST" action="{{ route('roles.horario', $usuario) }}" class="space-y-3">
                                @csrf
                                <div class="flex items-center justify-between">
                                    <x-input-label value="Restricción de horario" />
                                    <label class="relative inline-flex items-center shrink-0 cursor-pointer">
                                        <input type="checkbox" name="horario_activo" value="1" {{ $usuario->horario_activo ? 'checked' : '' }} class="peer sr-only">
                                        <span class="h-5 w-9 rounded-full bg-gray-200 peer-checked:bg-blue-700 transition-colors"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"></span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label :for="'hora_inicio_'.$usuario->id" value="Hora de entrada" class="text-xs" />
                                        <input type="time" id="hora_inicio_{{ $usuario->id }}" name="hora_inicio" value="{{ $usuario->hora_inicio }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    </div>
                                    <div>
                                        <x-input-label :for="'hora_fin_'.$usuario->id" value="Hora máxima" class="text-xs" />
                                        <input type="time" id="hora_fin_{{ $usuario->id }}" name="hora_fin" value="{{ $usuario->hora_fin }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    </div>
                                </div>

                                <div>
                                    <x-input-label value="Días de trabajo" class="text-xs mb-1" />
                                    <div class="flex flex-wrap gap-2">
                                        @php $diasActivos = array_filter(explode(',', $usuario->dias_trabajo ?? ''), fn ($d) => $d !== ''); @endphp
                                        @foreach ($diasSemana as $numero => $label)
                                            <label class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-gray-200 text-xs text-gray-600 cursor-pointer has-checked:bg-blue-50 has-checked:border-blue-200 has-checked:text-blue-700">
                                                <input type="checkbox" name="dias_trabajo[]" value="{{ $numero }}" {{ in_array((string) $numero, $diasActivos) ? 'checked' : '' }} class="h-3.5 w-3.5">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <button type="submit" class="w-full py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Guardar horario
                                </button>
                            </form>

                            <!-- Bloqueo de hoy -->
                            <form method="POST" action="{{ route('roles.bloqueo-hoy', $usuario) }}" class="border-t border-gray-100 pt-5">
                                @csrf
                                <button type="submit" class="w-full py-2 rounded-lg text-sm font-medium {{ $usuario->bloqueado_fecha?->isToday() ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-red-50 text-red-600 hover:bg-red-100' }}">
                                    {{ $usuario->bloqueado_fecha?->isToday() ? 'Quitar bloqueo de hoy' : 'Bloquear acceso solo hoy' }}
                                </button>
                            </form>
                        @else
                            <p class="text-xs text-gray-400">
                                No podés configurar horario ni bloqueo para tu propio usuario.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    </div>
</x-app-layout>
