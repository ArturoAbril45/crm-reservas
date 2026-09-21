<x-app-layout>
    <x-slot name="header">
        WhatsApp API
    </x-slot>

    <div x-data="{
            conectado: {{ $estado['conectado'] ? 'true' : 'false' }},
            numero: {{ \Illuminate\Support\Js::from($estado['numero'] ?? null) }},
            qr: {{ \Illuminate\Support\Js::from($estado['qr'] ?? null) }},
            error: {{ \Illuminate\Support\Js::from($estado['error'] ?? null) }},
            cargando: false,
            poll: null,
            actualizar() {
                fetch('{{ route('whatsapp.estado') }}')
                    .then(r => r.json())
                    .then(d => {
                        this.conectado = !!d.conectado;
                        this.numero = d.numero ?? null;
                        this.qr = d.qr ?? null;
                        this.error = d.error ?? null;
                    })
                    .catch(() => {});
            },
            init() {
                this.poll = setInterval(() => this.actualizar(), 4000);
            },
         }" x-init="init()">

        <div class="max-w-md mx-auto">

            <!-- Estado / QR -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                @if (session('status'))
                    <div class="mb-4 px-4 py-3 rounded-lg bg-[#9c0720]/10 text-[#9c0720] text-sm text-left">
                        {{ session('status') }}
                    </div>
                @endif

                <template x-if="conectado">
                    <div class="py-4">
                        <div class="flex items-center gap-3 rounded-xl border border-green-100 bg-green-50/60 px-4 py-3 text-left">
                            <div class="relative shrink-0">
                                <div class="h-11 w-11 rounded-full bg-white border border-gray-100 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-green-600">
                                        <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.05-1.32A10 10 0 1 0 12 2Zm0 18.2a8.17 8.17 0 0 1-4.17-1.14l-.3-.18-3.1.81.83-3.02-.2-.31A8.2 8.2 0 1 1 12 20.2Zm4.53-6.13c-.25-.12-1.46-.72-1.69-.8-.23-.08-.39-.12-.56.12-.16.25-.64.8-.79.96-.14.16-.29.18-.54.06-.25-.12-1.04-.38-1.98-1.22-.73-.65-1.23-1.46-1.37-1.7-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.35-.77-1.85-.2-.48-.4-.42-.56-.42h-.48c-.16 0-.43.06-.65.31-.23.25-.85.83-.85 2.03s.87 2.36.99 2.52c.12.16 1.71 2.61 4.14 3.66.58.25 1.03.4 1.38.51.58.18 1.11.16 1.53.1.47-.07 1.46-.6 1.66-1.17.21-.58.21-1.08.15-1.18-.06-.1-.23-.16-.48-.29Z"/>
                                    </svg>
                                </div>
                                <span class="absolute -bottom-0.5 -right-0.5 h-4 w-4 rounded-full bg-green-500 ring-2 ring-white flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="h-2.5 w-2.5">
                                        <path d="M20 6 9 17l-5-5" />
                                    </svg>
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">Número verificado</p>
                                <p class="text-sm text-gray-500 truncate" x-text="numero"></p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('whatsapp.desconectar') }}" class="mt-4">
                            @csrf
                            <button class="w-full py-2.5 rounded-lg border border-red-200 text-sm font-medium text-red-600 hover:bg-red-50">
                                Desconectar
                            </button>
                        </form>
                    </div>
                </template>

                <template x-if="!conectado">
                    <div class="py-4">
                        <template x-if="qr">
                            <img :src="qr" alt="Código QR de WhatsApp" class="mx-auto w-56 h-56 rounded-lg border border-gray-100 p-2">
                        </template>

                        <template x-if="!qr">
                            <div class="mx-auto w-56 h-56 rounded-lg border border-dashed border-gray-200 flex items-center justify-center text-sm text-gray-400">
                                Sin código QR todavía
                            </div>
                        </template>

                        <p class="text-sm text-gray-500 mt-4">
                            Escaneá el código con WhatsApp: Configuración → Dispositivos vinculados → Vincular dispositivo.
                        </p>

                        <template x-if="error">
                            <p class="text-xs text-red-500 mt-2" x-text="error"></p>
                        </template>

                        <form method="POST" action="{{ route('whatsapp.conectar') }}" class="mt-5" @submit="cargando = true">
                            @csrf
                            <x-primary-button class="w-full justify-center" :disabled="false">
                                <span x-show="!cargando">Generar QR</span>
                                <span x-show="cargando">Generando…</span>
                            </x-primary-button>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
