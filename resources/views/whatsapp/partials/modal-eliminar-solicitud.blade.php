<!-- Modal confirmar eliminación -->
<div x-show="eliminarAbierto === {{ $s->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
    <div @click.outside="eliminarAbierto = null" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-1">Eliminar solicitud</h3>
        <p class="text-sm text-gray-500 mb-5">
            ¿Eliminar la solicitud de <strong>{{ $s->nombre }}</strong>? Esta acción no se puede deshacer.
        </p>
        <form method="POST" action="{{ route('whatsapp.solicitudes.destroy', $s) }}" class="flex justify-end gap-2">
            @csrf
            @method('DELETE')
            <button type="button" @click="eliminarAbierto = null" class="py-2.5 px-4 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancelar</button>
            <button class="py-2.5 px-4 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Eliminar</button>
        </form>
    </div>
</div>
