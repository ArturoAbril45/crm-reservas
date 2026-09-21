<x-app-layout>
    <x-slot name="header">
        Solicitudes de reserva (WhatsApp)
    </x-slot>

    <div x-data="{
            verFotos: null, rechazar: null, motivo: '', sugerirAbierto: {{ $sugerirAbiertoInicial ? (int) $sugerirAbiertoInicial : 'null' }}, eliminarAbierto: null, masOpciones: null,
            chatAbierto: null, chatNombre: '', chatMensajes: [], chatTexto: '', chatEnviando: false, chatPoll: null,
            cerrarSugerir() {
                this.sugerirAbierto = null;
                // Limpia ?sugerir=&semana= de la URL sin recargar, para que un
                // refresh manual después de cerrar no vuelva a abrir el modal.
                window.history.replaceState(null, '', window.location.pathname);
            },
            csrfToken() {
                return document.querySelector('meta[name=csrf-token]').content;
            },
            scrollChatAbajo() {
                this.$nextTick(() => {
                    const el = this.$refs.chatMensajes;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },
            // Si ya estás abajo del todo (o casi), el autorefresco te sigue bajando
            // con los mensajes nuevos. Pero si te subiste a leer mensajes viejos,
            // no te mueve la pantalla — así se puede leer el historial tranquilo.
            chatCercaDelFinal() {
                const el = this.$refs.chatMensajes;
                if (! el) return true;

                return (el.scrollHeight - el.scrollTop - el.clientHeight) < 80;
            },
            abrirChat(numero, nombre) {
                this.chatAbierto = numero;
                this.chatNombre = nombre;
                this.chatMensajes = [];
                this.chatTexto = '';

                fetch(`/whatsapp/chat/${numero}/abrir`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken() },
                }).then(r => r.json()).then(d => {
                    this.chatMensajes = d.mensajes ?? [];
                    this.scrollChatAbajo();
                });

                clearInterval(this.chatPoll);
                this.chatPoll = setInterval(() => {
                    if (! this.chatAbierto) return;
                    const seguirAlFinal = this.chatCercaDelFinal();

                    fetch(`/whatsapp/chat/${this.chatAbierto}/mensajes`)
                        .then(r => r.json())
                        .then(d => {
                            this.chatMensajes = d.mensajes ?? [];
                            if (seguirAlFinal) this.scrollChatAbajo();
                        });
                }, 4000);
            },
            cerrarChat() {
                // Al cerrar el chat se le devuelve el número al bot automático —
                // si no, el cliente se queda sin respuestas hasta que alguien
                // se acuerde de reanudarlo a mano.
                if (this.chatAbierto) {
                    fetch(`/whatsapp/chat/${this.chatAbierto}/reanudar`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken() },
                    });
                }
                this.chatAbierto = null;
                clearInterval(this.chatPoll);
            },
            enviarChatMensaje() {
                if (! this.chatTexto.trim() || this.chatEnviando) return;
                this.chatEnviando = true;

                const fd = new FormData();
                fd.append('mensaje', this.chatTexto);

                fetch(`/whatsapp/chat/${this.chatAbierto}/enviar`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken() },
                    body: fd,
                }).then(r => r.json()).then(d => {
                    if (d.mensajes) {
                        this.chatMensajes = d.mensajes;
                        this.chatTexto = '';
                        this.scrollChatAbajo();
                    } else if (d.error) {
                        alert(d.error);
                    }
                }).finally(() => { this.chatEnviando = false; });
            },
            init() {
                // Autorefresco: si un cliente responde por WhatsApp (ej. elige un cuarto
                // sugerido) el cambio no se ve hasta recargar. Refresca solo, pero no
                // mientras haya un modal/formulario abierto para no interrumpirte.
                setInterval(() => {
                    if (! this.verFotos && this.rechazar === null && this.sugerirAbierto === null && this.eliminarAbierto === null && ! this.chatAbierto && this.masOpciones === null) {
                        window.location.reload();
                    }
                }, 15000);
            },
         }" class="space-y-6">
        @if (session('status'))
            <div class="px-4 py-3 rounded-xl bg-[#9c0720]/10 text-[#9c0720] text-sm border border-[#9c0720]/15">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Pendientes -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-visible">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                <h2 class="font-semibold text-gray-900">Pendientes de revisión</h2>
                <span class="text-xs text-gray-400">({{ $pendientes->count() }})</span>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse ($pendientes as $s)
                    <div class="px-6 py-4">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div class="min-w-0 max-w-full break-words">
                                <p class="font-medium text-gray-900">{{ $s->nombre }} <span class="text-gray-400 font-normal">· {{ $s->cedula }}</span></p>
                                <p class="text-sm font-semibold text-gray-500 mt-0.5">{{ $s->local }} — Cuarto {{ $s->cuarto }} · Semana: {{ $s->semana_texto }}</p>
                                <p class="text-sm font-semibold text-gray-400 mt-0.5">Pidió el {{ $s->created_at->format('d/m/Y H:i') }}</p>
                                <div class="flex gap-3 mt-2">
                                    @if ($s->foto_lateral || $s->foto_posterior)
                                        <button @click="verFotos = {{ \Illuminate\Support\Js::from(array_values(array_filter([
                                                $s->foto_lateral ? asset('storage/' . $s->foto_lateral) : null,
                                                $s->foto_posterior ? asset('storage/' . $s->foto_posterior) : null,
                                            ]))) }}" class="text-xs font-medium text-[#9c0720] hover:text-[#7c0519]">Ver cédula</button>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap justify-end">
                                <button @click="abrirChat({{ \Illuminate\Support\Js::from($s->numero) }}, {{ \Illuminate\Support\Js::from($s->nombre) }})" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50">Chat en vivo</button>
                                <form method="POST" action="{{ route('whatsapp.solicitudes.aprobar', $s) }}" onsubmit="return confirm('¿Aprobar esta solicitud? Se le va a avisar al cliente para que deposite la prenda.')">
                                    @csrf
                                    <button class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Aprobar</button>
                                </form>
                                <button @click="rechazar = {{ $s->id }}; motivo = ''" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">Rechazar</button>
                                <div class="relative">
                                    <button type="button" @click="masOpciones = (masOpciones === {{ $s->id }} ? null : {{ $s->id }})" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50">
                                        Más opciones
                                    </button>
                                    <div x-show="masOpciones === {{ $s->id }}" x-cloak @click.outside="masOpciones = null"
                                         class="absolute right-0 top-full mt-1 z-20 w-56 rounded-lg border border-gray-100 bg-white p-2 shadow-lg space-y-1.5">
                                        <button type="button" @click="masOpciones = null; sugerirAbierto = {{ $s->id }}"
                                                class="w-full text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-[#9c0720]/25 text-[#9c0720] hover:bg-[#9c0720]/10">
                                            Sugerir habitación
                                        </button>
                                        <form method="POST" action="{{ route('whatsapp.solicitudes.confirmar-prenda', $s) }}" onsubmit="return confirm('¿Confirmar esta reserva directo, sin pedirle depósito al cliente? Se ancla al tablero de Reservas y no se le va a mandar el mensaje de depósito.')">
                                            @csrf
                                            <button class="w-full text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-[#9c0720]/25 text-[#9c0720] hover:bg-[#9c0720]/10">
                                                Confirmar sin depósito
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal sugerir habitación disponible -->
                        <div x-show="sugerirAbierto === {{ $s->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8 overflow-y-auto" style="display: none;">
                            <div @click.outside="cerrarSugerir()" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 my-auto">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="font-semibold text-gray-900">Sugerir habitación a {{ $s->nombre }}</h3>
                                    <button @click="cerrarSugerir()" class="text-gray-400 hover:text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                            <path d="M18 6 6 18M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Pidió: {{ $s->local }} — Cuarto {{ $s->cuarto }} · Semana: {{ $s->semana_texto }}</p>

                                <!-- Navegación de semana: ver disponibilidad de otras semanas antes de sugerir -->
                                <div class="flex items-center justify-between mb-4 bg-gray-50 rounded-xl px-3 py-2">
                                    <a href="{{ route('whatsapp.solicitudes', ['sugerir' => $s->id, 'semana' => $semanasSugerencia[$s->id]->copy()->subWeek()->toDateString()]) }}"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 hover:text-[#9c0720]">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="m15 18-6-6 6-6" /></svg>
                                        Semana anterior
                                    </a>
                                    <div class="text-center">
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Viendo disponibilidad</p>
                                        <p class="text-xs font-semibold text-gray-800">{{ $semanasSugerencia[$s->id]->format('d/m/Y') }} — {{ $semanasSugerencia[$s->id]->copy()->addDays(6)->format('d/m/Y') }}</p>
                                    </div>
                                    <a href="{{ route('whatsapp.solicitudes', ['sugerir' => $s->id, 'semana' => $semanasSugerencia[$s->id]->copy()->addWeek()->toDateString()]) }}"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 hover:text-[#9c0720]">
                                        Semana siguiente
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="m9 18 6-6-6-6" /></svg>
                                    </a>
                                </div>

                                <p class="text-xs text-gray-400 mb-4">Elegí uno o varios cuartos disponibles (verde) para sugerirle al cliente. Los ocupados (rojo) no se pueden elegir.</p>

                                <form method="POST" action="{{ route('whatsapp.solicitudes.sugerir', $s) }}"
                                      x-data="{ seleccion: [] }"
                                      @submit="if (seleccion.length === 0) { $event.preventDefault(); }">
                                    @csrf

                                    @foreach (($disponibilidad[$s->id] ?? []) as $grupo)
                                        <div class="mb-4">
                                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ $grupo['sucursal'] }}</p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($grupo['habitaciones'] as $habitacion)
                                                    @php $valor = $grupo['sucursal'] . '|' . $habitacion['numero']; @endphp
                                                    @if ($habitacion['ocupada'])
                                                        <span class="px-3 py-1.5 rounded-lg bg-red-500 text-white text-sm font-medium opacity-80" title="{{ $habitacion['cliente'] }}">
                                                            Cuarto {{ $habitacion['numero'] }}
                                                        </span>
                                                    @else
                                                        <label class="px-3 py-1.5 rounded-lg border text-sm font-medium cursor-pointer select-none"
                                                               :class="seleccion.includes('{{ $valor }}') ? 'bg-green-600 border-green-600 text-white' : 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100'">
                                                            <input type="checkbox" name="seleccion[]" value="{{ $valor }}" x-model="seleccion" class="sr-only">
                                                            Cuarto {{ $habitacion['numero'] }}
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" @click="cerrarSugerir()" class="py-1.5 px-3 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50">Cerrar</button>
                                        <button type="submit" :disabled="seleccion.length === 0"
                                                class="py-1.5 px-3 rounded-lg bg-[#9c0720] text-white text-xs font-medium hover:bg-[#7c0519] disabled:opacity-40 disabled:cursor-not-allowed">
                                            Enviar sugerencia <span x-show="seleccion.length" x-text="'(' + seleccion.length + ')'"></span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Formulario de rechazo (inline) -->
                        <div x-show="rechazar === {{ $s->id }}" x-cloak class="mt-3 p-3 rounded-xl bg-red-50 border border-red-100">
                            <form method="POST" action="{{ route('whatsapp.solicitudes.rechazar', $s) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="text" name="motivo_rechazo" x-model="motivo" placeholder="Motivo (opcional) — ej. ofrecer otro cuarto, otra semana..."
                                       class="flex-1 rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-400">
                                <button type="button" @click="rechazar = null" class="text-xs font-medium text-gray-500 px-2">Cancelar</button>
                                <button class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-red-600 text-white hover:bg-red-700">Confirmar rechazo</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-gray-400">No hay solicitudes pendientes.</p>
                @endforelse
            </div>
        </div>

        <!-- Esperando prenda -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-[#9c0720]/50"></span>
                <h2 class="font-semibold text-gray-900">Aprobadas — esperando prenda</h2>
                <span class="text-xs text-gray-400">({{ $esperandoPrenda->count() }})</span>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse ($esperandoPrenda as $s)
                    <div class="px-6 py-4 flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <p class="font-medium text-gray-900">{{ $s->nombre }} <span class="text-gray-400 font-normal">· {{ $s->cedula }}</span></p>
                            <p class="text-sm font-semibold text-gray-500 mt-0.5">{{ $s->local }} — Cuarto {{ $s->cuarto }} · Semana: {{ $s->semana_texto }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-[#9c0720]/10 text-[#9c0720]">Esperando depósito del cliente</span>
                            <button type="button" @click="eliminarAbierto = {{ $s->id }}" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">Eliminar</button>
                        </div>
                        @include('whatsapp.partials.modal-eliminar-solicitud', ['s' => $s])
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-gray-400">Ninguna en este estado.</p>
                @endforelse
            </div>
        </div>

        <!-- Prenda en revisión -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                <h2 class="font-semibold text-gray-900">Prenda en revisión</h2>
                <span class="text-xs text-gray-400">({{ $prendaEnRevision->count() }})</span>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse ($prendaEnRevision as $s)
                    <div class="px-6 py-4 flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <p class="font-medium text-gray-900">{{ $s->nombre }} <span class="text-gray-400 font-normal">· {{ $s->cedula }}</span></p>
                            <p class="text-sm font-semibold text-gray-500 mt-0.5">{{ $s->local }} — Cuarto {{ $s->cuarto }} · Semana: {{ $s->semana_texto }}</p>
                            @if ($s->prenda_comprobante)
                                <button @click="verFotos = ['{{ asset('storage/' . $s->prenda_comprobante) }}']" class="text-xs font-medium text-[#9c0720] hover:text-[#7c0519] mt-1">Ver comprobante de depósito</button>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('whatsapp.solicitudes.confirmar-prenda', $s) }}" onsubmit="return confirm('¿Confirmar la prenda y la reserva? Se le va a avisar al cliente.')">
                                @csrf
                                <button class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Confirmar prenda</button>
                            </form>
                            <button type="button" @click="eliminarAbierto = {{ $s->id }}" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">Eliminar</button>
                        </div>
                        @include('whatsapp.partials.modal-eliminar-solicitud', ['s' => $s])
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-gray-400">Ninguna en este estado.</p>
                @endforelse
            </div>
        </div>

        <!-- Confirmadas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                <h2 class="font-semibold text-gray-900">Confirmadas</h2>
                <span class="text-xs text-gray-400">({{ $confirmadas->count() }})</span>
            </div>
            <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                @forelse ($confirmadas as $s)
                    <div class="px-6 py-3 flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <p class="text-sm font-semibold text-gray-700">{{ $s->nombre }} — {{ $s->local }} Cuarto {{ $s->cuarto }} · {{ $s->semana_texto }}</p>
                            <span class="text-sm font-semibold text-gray-400">{{ $s->revisada_en?->format('d/m/Y H:i') }}</span>
                            @if ($s->foto_lateral || $s->foto_posterior)
                                <button @click="verFotos = {{ \Illuminate\Support\Js::from(array_values(array_filter([
                                        $s->foto_lateral ? asset('storage/' . $s->foto_lateral) : null,
                                        $s->foto_posterior ? asset('storage/' . $s->foto_posterior) : null,
                                    ]))) }}" class="block text-xs font-medium text-[#9c0720] hover:text-[#7c0519] mt-0.5">Ver cédula</button>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($ancladas->has($s->id))
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Anclada en Reservas</span>
                            @else
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Sin anclar — revisá cuarto/semana</span>
                            @endif
                            <button type="button" @click="eliminarAbierto = {{ $s->id }}" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">Eliminar</button>
                        </div>
                        @include('whatsapp.partials.modal-eliminar-solicitud', ['s' => $s])
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-gray-400">Ninguna todavía.</p>
                @endforelse
            </div>
        </div>

        <!-- Rechazadas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-red-400"></span>
                <h2 class="font-semibold text-gray-900">Rechazadas</h2>
                <span class="text-xs text-gray-400">({{ $rechazadas->count() }})</span>
            </div>
            <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                @forelse ($rechazadas as $s)
                    <div class="px-6 py-3 flex items-center justify-between gap-4">
                        <p class="text-sm font-semibold text-gray-700">{{ $s->nombre }} — {{ $s->local }} Cuarto {{ $s->cuarto }} · {{ $s->semana_texto }}</p>
                        <p class="text-sm font-semibold text-gray-400">{{ $s->motivo_rechazo ?? 'Sin motivo' }}</p>
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-gray-400">Ninguna todavía.</p>
                @endforelse
            </div>
        </div>

        <!-- Modal ver foto(s) -->
        <div x-show="verFotos" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click="verFotos = null" style="display: none;">
            <div class="flex flex-wrap gap-4 items-center justify-center max-w-[95vw] max-h-[90vh]" @click.stop>
                <template x-for="foto in (verFotos || [])" :key="foto">
                    <div class="min-w-0 shrink">
                        <img :src="foto" class="max-h-[75vh] max-w-[90vw] sm:max-w-[45vw] rounded-lg bg-white object-contain shadow-2xl">
                    </div>
                </template>
            </div>
        </div>

        <!-- Modal Chat en vivo -->
        <div x-show="chatAbierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8" style="display: none;">
            <div @click.outside="cerrarChat()" class="bg-white rounded-2xl shadow-xl w-full max-w-md flex flex-col" style="height: 80vh;">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div>
                        <p class="font-semibold text-gray-900" x-text="chatNombre"></p>
                        <p class="text-xs text-emerald-600 flex items-center gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Chat en vivo — el bot automático está pausado con este cliente
                        </p>
                    </div>
                    <button @click="cerrarChat()" class="text-gray-400 hover:text-gray-600 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div x-ref="chatMensajes" class="flex-1 overflow-y-auto px-4 py-3 space-y-2 bg-gray-50">
                    <template x-if="chatMensajes.length === 0">
                        <p class="text-center text-sm text-gray-400 mt-6">Todavía no hay mensajes.</p>
                    </template>
                    <template x-for="(m, i) in chatMensajes" :key="i">
                        <div class="flex" :class="m.origen === 'cliente' ? 'justify-start' : 'justify-end'">
                            <div class="max-w-[80%] rounded-2xl px-3.5 py-2 text-sm"
                                 :class="m.origen === 'cliente' ? 'bg-white border border-gray-200 text-gray-800' : (m.origen === 'admin' ? 'bg-[#9c0720] text-white' : 'bg-emerald-100 text-emerald-800')">
                                <img x-show="m.imagen" :src="m.imagen" class="rounded-lg mb-1 max-h-48 object-contain">
                                <p x-text="m.texto" class="whitespace-pre-wrap"></p>
                                <p class="text-[10px] mt-1 opacity-60" x-text="m.hora"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-4 py-3 border-t border-gray-100 shrink-0">
                    <form @submit.prevent="enviarChatMensaje()" class="flex items-center gap-2">
                        <input type="text" x-model="chatTexto" placeholder="Escribí un mensaje..." :disabled="chatEnviando"
                               class="flex-1 rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                        <button type="submit" :disabled="chatEnviando || ! chatTexto.trim()"
                                class="rounded-lg bg-[#9c0720] text-white px-4 py-2.5 text-sm font-semibold hover:bg-[#7c0519] disabled:opacity-40 disabled:cursor-not-allowed">
                            Enviar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
