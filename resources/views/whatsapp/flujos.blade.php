<x-app-layout>
    <x-slot name="header">
        Conexión de flujos
    </x-slot>

    <style>
        .flujo-nodo-sin-salida { border-style: dashed !important; border-color: #ef4444 !important; }
        .flujo-nodo-sin-salida::after {
            content: '⚠';
            position: absolute; top: -10px; left: -10px;
            background: #ef4444; color: white; font-size: 10px;
            width: 18px; height: 18px; border-radius: 9999px;
            display: flex; align-items: center; justify-content: center;
        }
    </style>

    @if ($conversacionesActivas > 0)
        <div class="mb-4 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" /><path d="M12 9v4M12 17h.01" />
            </svg>
            Ojo: ahora mismo hay {{ $conversacionesActivas }} {{ $conversacionesActivas === 1 ? 'cliente a mitad de una reserva' : 'clientes a mitad de una reserva' }} por WhatsApp. Si cambiás o borrás el paso en el que están, esa conversación se reinicia sola desde el principio.
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-gray-900">Flujo del bot de WhatsApp</h2>
                <p class="text-xs text-gray-500 mt-0.5">Arrastrá los cuadros para moverlos. Tocá uno para editar su mensaje. Arrastrá desde el punto de la derecha hasta otro cuadro para conectar. Los cuadros con borde rojo punteado no tienen a dónde seguir.</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <button type="button" id="btn-deshacer" onclick="deshacerUltimo()" disabled class="text-xs font-semibold px-3 py-2 rounded-lg border border-gray-200 text-gray-400 disabled:opacity-50 hover:bg-gray-50 hover:text-gray-700 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                        <path d="M9 14 4 9l5-5" /><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11" />
                    </svg>
                    Deshacer
                </button>
                <button type="button" onclick="ordenarAutomaticamente()" class="text-xs font-semibold px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                    Ordenar cuadros
                </button>
                <button type="button" onclick="crearNodo('pregunta_texto')" class="text-xs font-semibold px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                    + Pregunta (texto)
                </button>
                <button type="button" onclick="crearNodo('pregunta_foto')" class="text-xs font-semibold px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                    + Pregunta (foto)
                </button>
                <button type="button" onclick="crearNodo('mensaje_final')" class="text-xs font-semibold px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                    + Mensaje final
                </button>
                <button type="button" onclick="abrirPrueba()" class="text-xs font-semibold px-3 py-2 rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z" />
                    </svg>
                    Probar flujo
                </button>
                <button type="button" onclick="restaurarFlujo()" class="text-xs font-semibold px-3 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50">
                    Restaurar flujo original
                </button>
                <button type="button" onclick="guardarCambios()" class="text-xs font-semibold px-3 py-2 rounded-lg bg-[#9c0720] text-white hover:bg-[#7c0519] flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" /><path d="M17 21v-8H7v8M7 3v5h8" />
                    </svg>
                    Guardar cambios
                </button>
            </div>
        </div>

        <div id="flujo-scroll" class="relative overflow-auto" style="height: 75vh;">
            <div id="flujo-canvas" class="relative cursor-grab active:cursor-grabbing" style="width: 3300px; height: 900px; background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 20px 20px;">
                <svg id="flujo-svg" class="absolute inset-0 pointer-events-none" width="3300" height="900" style="z-index: 1;">
                    <defs>
                        <marker id="flecha" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto">
                            <path d="M0,0 L0,6 L9,3 z" fill="#94a3b8" />
                        </marker>
                    </defs>
                </svg>

                @foreach ($nodos as $nodo)
                    @php
                        $colores = match ($nodo->tipo) {
                            'pregunta_texto', 'pregunta_foto', 'elegir_local' => 'border-[#9c0720]/25 bg-[#9c0720]/10',
                            'mensaje_final' => 'border-gray-200 bg-gray-50',
                            'menu_inicial' => 'border-amber-200 bg-amber-50',
                            'plantilla_admin' => 'border-teal-200 bg-teal-50',
                            default => 'border-purple-200 bg-purple-50',
                        };
                        $badge = match ($nodo->tipo) {
                            'pregunta_texto' => 'Pregunta (texto)',
                            'pregunta_foto' => 'Pregunta (foto)',
                            'mensaje_final' => 'Mensaje final',
                            'menu_inicial' => 'Menú inicial',
                            'elegir_local' => 'Menú (Casa Blanca / VIP)',
                            'guardar_cliente' => 'Sistema · Guardar cliente',
                            'crear_solicitud' => 'Sistema · Enviar solicitud',
                            'esperar_comprobante_prenda' => 'Sistema · Esperar comprobante',
                            'plantilla_admin' => 'Plantilla del admin',
                            default => $nodo->tipo,
                        };
                    @endphp
                    <div class="flujo-nodo absolute w-36 h-36 rounded-xl border-2 shadow-sm cursor-move select-none flex flex-col {{ $colores }}"
                         data-id="{{ $nodo->id }}"
                         style="left: {{ $nodo->pos_x }}px; top: {{ $nodo->pos_y }}px; z-index: 2;">
                        <div class="px-2.5 py-2 flex-1 min-h-0 flex flex-col">
                            <p class="text-[9px] font-semibold uppercase tracking-wide text-gray-500 leading-tight">{{ $badge }}</p>
                            <p class="text-xs font-semibold text-gray-900 mt-1 leading-tight line-clamp-2">{{ $nodo->titulo }}</p>
                            <p class="text-[10px] text-gray-500 mt-1 leading-snug overflow-hidden flex-1">{{ Str::limit($nodo->mensaje, 90) }}</p>
                        </div>
                        @if ($nodo->eliminable)
                            <button type="button" onclick="eliminarNodo({{ $nodo->id }})" class="flujo-eliminar absolute -top-2 -right-2 h-5 w-5 rounded-full bg-white border border-gray-200 text-gray-400 hover:text-red-600 hover:border-red-200 text-xs flex items-center justify-center">✕</button>
                        @endif
                        <div class="flujo-puerto-entrada absolute -left-2 top-1/2 -translate-y-1/2 h-3 w-3 rounded-full bg-gray-300 border-2 border-white"></div>
                        <div class="flujo-puerto-salida absolute -right-2 top-1/2 -translate-y-1/2 h-3 w-3 rounded-full bg-[#9c0720]/70 border-2 border-white cursor-crosshair"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Panel de edición -->
    <div id="flujo-panel" class="fixed inset-y-0 right-0 w-full max-w-sm bg-white shadow-2xl border-l border-gray-100 z-50 translate-x-full transition-transform duration-200 overflow-y-auto">
        <div class="p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Editar paso</h3>
                <button type="button" onclick="cerrarPanel()" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <x-input-label value="Título" />
                    <x-text-input id="panel-titulo" type="text" class="mt-1.5" />
                </div>
                <div>
                    <x-input-label value="Mensaje que manda el bot" />
                    <textarea id="panel-mensaje" rows="6" class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]"></textarea>
                    <p class="mt-1 text-xs text-gray-400">Usá <code>{numero}</code> donde quieras que aparezca el número de la habitación (solo en los pasos del sistema que lo mencionan).</p>
                </div>
                <div>
                    <x-input-label value="Foto que manda junto al mensaje (opcional)" />
                    <div id="panel-imagen-preview" class="mt-1.5 hidden">
                        <img id="panel-imagen-img" src="" class="w-full h-32 object-cover rounded-lg border border-gray-200">
                        <button type="button" onclick="quitarImagenNodo()" class="mt-1.5 text-xs font-medium text-red-600 hover:text-red-700">Quitar foto</button>
                    </div>
                    <input type="file" id="panel-imagen-input" accept="image/*" onchange="subirImagenNodo(this)"
                           class="mt-1.5 block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-[#9c0720]/10 file:text-[#9c0720] file:text-xs file:font-medium">
                    <p id="panel-imagen-status" class="mt-1 text-xs text-gray-400"></p>
                </div>
                <div id="panel-campo-wrapper">
                    <x-input-label value="Guardar la respuesta en" />
                    <select id="panel-campo" class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                        <option value="">— No guardar —</option>
                        @foreach ($camposDisponibles as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" onclick="guardarNodo()" class="w-full py-2.5 rounded-lg bg-[#9c0720] text-white text-sm font-semibold hover:bg-[#7c0519]">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
    <div id="flujo-overlay" onclick="cerrarPanel()" class="fixed inset-0 bg-black/20 z-40 hidden"></div>

    <div id="flujo-toast" class="fixed bottom-6 right-6 z-[60] px-4 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-medium shadow-lg opacity-0 pointer-events-none transition-opacity duration-300 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-green-400"><path d="M20 6 9 17l-5-5" /></svg>
        <span id="flujo-toast-texto">Guardado</span>
    </div>

    <!-- Modal probar flujo -->
    <div id="prueba-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm flex flex-col" style="height: 80vh;">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div>
                    <h3 class="font-semibold text-gray-900">Probar flujo</h3>
                    <p class="text-xs text-gray-500">Simulación: no se guarda ningún cliente ni reserva de verdad.</p>
                </div>
                <button type="button" onclick="cerrarPrueba()" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="prueba-chat" class="flex-1 overflow-y-auto p-4 space-y-2 bg-gray-50"></div>

            <div class="p-3 border-t border-gray-100 flex items-center gap-2">
                <button type="button" onclick="reiniciarPrueba()" title="Empezar de nuevo" class="shrink-0 h-9 w-9 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8" /><path d="M3 3v5h5" />
                    </svg>
                </button>
                <input id="prueba-input" type="text" placeholder="Escribí como si fueras el cliente..."
                       class="flex-1 rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                <button type="button" onclick="enviarPrueba()" class="shrink-0 h-9 w-9 rounded-lg bg-[#9c0720] text-white hover:bg-[#7c0519] flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="m22 2-7 20-4-9-9-4Z" /><path d="M22 2 11 13" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const nodosData = @json($nodos->keyBy('id'));
        let conexiones = @json($conexiones);
        const canvas = document.getElementById('flujo-canvas');
        const svg = document.getElementById('flujo-svg');
        let nodoSeleccionadoId = null;
        let arrastrando = null; // { id, offsetX, offsetY, movio }
        let dibujandoConexion = null; // { origenId, x1, y1 }

        // --- Aviso de guardado ---
        let toastTimeout = null;
        function mostrarGuardado(texto = 'Guardado') {
            const toast = document.getElementById('flujo-toast');
            document.getElementById('flujo-toast-texto').textContent = texto;
            toast.classList.remove('opacity-0');
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => toast.classList.add('opacity-0'), 1800);
        }

        function guardarCambios() {
            mostrarGuardado('Todos los cambios están guardados');
        }

        // --- Deshacer ---
        let historial = [];

        function registrarAccion(deshacer) {
            historial.push(deshacer);
            document.getElementById('btn-deshacer').disabled = false;
        }

        async function deshacerUltimo() {
            if (historial.length === 0) return;
            const fn = historial.pop();
            if (historial.length === 0) document.getElementById('btn-deshacer').disabled = true;
            await fn();
            mostrarGuardado('Cambio deshecho');
        }

        function centroNodo(id, lado) {
            const el = canvas.querySelector('.flujo-nodo[data-id="' + id + '"]');
            if (!el) return null;
            const x = parseInt(el.style.left);
            const y = parseInt(el.style.top);
            const w = el.offsetWidth;
            const h = el.offsetHeight;
            return lado === 'salida' ? { x: x + w, y: y + h / 2 } : { x: x, y: y + h / 2 };
        }

        function pathEntreCentros(a, b) {
            const dx = Math.max(40, Math.abs(b.x - a.x) / 2);
            return `M ${a.x} ${a.y} C ${a.x + dx} ${a.y}, ${b.x - dx} ${b.y}, ${b.x} ${b.y}`;
        }

        function redibujarConexiones() {
            svg.querySelectorAll('.flujo-linea, .flujo-linea-hit, .flujo-etiqueta').forEach(el => el.remove());

            conexiones.forEach(cx => {
                const a = centroNodo(cx.nodo_origen_id, 'salida');
                const b = centroNodo(cx.nodo_destino_id, 'entrada');
                if (!a || !b) return;

                const d = pathEntreCentros(a, b);

                const linea = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                linea.setAttribute('d', d);
                linea.setAttribute('class', 'flujo-linea');
                linea.setAttribute('stroke', '#94a3b8');
                linea.setAttribute('stroke-width', '2');
                linea.setAttribute('fill', 'none');
                linea.setAttribute('marker-end', 'url(#flecha)');
                svg.appendChild(linea);

                const hit = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                hit.setAttribute('d', d);
                hit.setAttribute('class', 'flujo-linea-hit');
                hit.setAttribute('stroke', 'transparent');
                hit.setAttribute('stroke-width', '14');
                hit.setAttribute('fill', 'none');
                hit.style.pointerEvents = 'stroke';
                hit.style.cursor = 'pointer';
                hit.onmouseenter = () => {
                    linea.setAttribute('stroke', '#ef4444');
                    linea.setAttribute('stroke-width', '3');
                    hit.setAttribute('title', 'Tocá para eliminar esta conexión');
                };
                hit.onmouseleave = () => {
                    linea.setAttribute('stroke', '#94a3b8');
                    linea.setAttribute('stroke-width', '2');
                };
                hit.onclick = () => eliminarConexion(cx.id);
                svg.appendChild(hit);

                if (cx.valor || cx.etiqueta) {
                    const mx = (a.x + b.x) / 2, my = (a.y + b.y) / 2;
                    const texto = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    texto.setAttribute('x', mx);
                    texto.setAttribute('y', my - 6);
                    texto.setAttribute('class', 'flujo-etiqueta');
                    texto.setAttribute('font-size', '10');
                    texto.setAttribute('fill', '#2563eb');
                    texto.setAttribute('text-anchor', 'middle');
                    texto.textContent = cx.etiqueta || cx.valor;
                    svg.appendChild(texto);
                }
            });

            if (dibujandoConexion) {
                const linea = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                linea.setAttribute('d', pathEntreCentros(
                    { x: dibujandoConexion.x1, y: dibujandoConexion.y1 },
                    { x: dibujandoConexion.x2, y: dibujandoConexion.y2 }
                ));
                linea.setAttribute('stroke', '#2563eb');
                linea.setAttribute('stroke-width', '2');
                linea.setAttribute('stroke-dasharray', '4');
                linea.setAttribute('fill', 'none');
                svg.appendChild(linea);
            }
        }

        // --- Arrastrar nodos / paneo del fondo ---
        const UMBRAL_CLICK = 5; // px de tolerancia antes de considerarlo un arrastre
        const scrollBox = document.getElementById('flujo-scroll');
        let paneando = null; // { startClientX, startClientY, startScrollLeft, startScrollTop, movio }

        canvas.addEventListener('mousedown', (e) => {
            const puertoSalida = e.target.closest('.flujo-puerto-salida');
            const nodoEl = e.target.closest('.flujo-nodo');

            if (!nodoEl) {
                // Se hizo mousedown en el fondo del canvas: arrancar paneo.
                paneando = {
                    startClientX: e.clientX, startClientY: e.clientY,
                    startScrollLeft: scrollBox.scrollLeft, startScrollTop: scrollBox.scrollTop,
                };
                return;
            }

            const id = parseInt(nodoEl.dataset.id);

            if (puertoSalida) {
                const c = centroNodo(id, 'salida');
                dibujandoConexion = { origenId: id, x1: c.x, y1: c.y, x2: c.x, y2: c.y };
                e.preventDefault();
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const startX = e.clientX - rect.left - parseInt(nodoEl.style.left);
            const startY = e.clientY - rect.top - parseInt(nodoEl.style.top);
            const oldX = parseInt(nodoEl.style.left);
            const oldY = parseInt(nodoEl.style.top);
            arrastrando = { id, nodoEl, startX, startY, oldX, oldY, startClientX: e.clientX, startClientY: e.clientY, movio: false };
        });

        document.addEventListener('mousemove', (e) => {
            const rect = canvas.getBoundingClientRect();

            if (arrastrando) {
                const desplazado = Math.abs(e.clientX - arrastrando.startClientX) > UMBRAL_CLICK
                    || Math.abs(e.clientY - arrastrando.startClientY) > UMBRAL_CLICK;

                if (desplazado) {
                    const x = e.clientX - rect.left - arrastrando.startX;
                    const y = e.clientY - rect.top - arrastrando.startY;
                    arrastrando.nodoEl.style.left = Math.max(0, x) + 'px';
                    arrastrando.nodoEl.style.top = Math.max(0, y) + 'px';
                    arrastrando.movio = true;
                    redibujarConexiones();
                }
            }

            if (paneando) {
                scrollBox.scrollLeft = paneando.startScrollLeft - (e.clientX - paneando.startClientX);
                scrollBox.scrollTop = paneando.startScrollTop - (e.clientY - paneando.startClientY);
            }

            if (dibujandoConexion) {
                dibujandoConexion.x2 = e.clientX - rect.left;
                dibujandoConexion.y2 = e.clientY - rect.top;
                redibujarConexiones();
            }
        });

        document.addEventListener('mouseup', (e) => {
            if (paneando) {
                paneando = null;
            }

            if (arrastrando) {
                const { id, nodoEl, movio, oldX, oldY } = arrastrando;
                arrastrando = null;

                if (movio) {
                    const nuevoX = parseInt(nodoEl.style.left);
                    const nuevoY = parseInt(nodoEl.style.top);
                    fetch(`/flujos/nodos/${id}/mover`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ pos_x: nuevoX, pos_y: nuevoY }),
                    }).then(() => mostrarGuardado());
                    registrarAccion(async () => {
                        nodoEl.style.left = oldX + 'px';
                        nodoEl.style.top = oldY + 'px';
                        redibujarConexiones();
                        await fetch(`/flujos/nodos/${id}/mover`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                            body: JSON.stringify({ pos_x: oldX, pos_y: oldY }),
                        });
                    });
                } else {
                    abrirPanel(id);
                }
            }

            if (dibujandoConexion) {
                const nodoDestino = e.target.closest('.flujo-nodo');
                if (nodoDestino && parseInt(nodoDestino.dataset.id) !== dibujandoConexion.origenId) {
                    const destinoId = parseInt(nodoDestino.dataset.id);
                    const valor = prompt('¿Con qué palabra clave se activa esta conexión? (Ej: 1, si, no — dejalo vacío si siempre sigue por acá)') || null;
                    fetch('/flujos/conexiones', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ nodo_origen_id: dibujandoConexion.origenId, nodo_destino_id: destinoId, valor: valor }),
                    }).then(r => r.json()).then(data => {
                        conexiones.push(data.conexion);
                        redibujarConexiones();
                        actualizarAvisos();
                        mostrarGuardado();
                        registrarAccion(async () => {
                            conexiones = conexiones.filter(c => c.id !== data.conexion.id);
                            redibujarConexiones();
                            actualizarAvisos();
                            await fetch(`/flujos/conexiones/${data.conexion.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } });
                        });
                    });
                }
                dibujandoConexion = null;
                redibujarConexiones();
            }
        });

        // --- Panel de edición ---
        function abrirPanel(id) {
            nodoSeleccionadoId = id;
            const nodo = nodosData[id];
            document.getElementById('panel-titulo').value = nodo.titulo;
            document.getElementById('panel-mensaje').value = nodo.mensaje || '';
            document.getElementById('panel-campo').value = nodo.campo_destino || '';

            const mostrarCampo = nodo.tipo === 'pregunta_texto' || nodo.tipo === 'pregunta_foto';
            document.getElementById('panel-campo-wrapper').style.display = mostrarCampo ? 'block' : 'none';

            actualizarPreviewImagenNodo(nodo.imagen);
            document.getElementById('panel-imagen-status').textContent = '';

            document.getElementById('flujo-panel').classList.remove('translate-x-full');
            document.getElementById('flujo-overlay').classList.remove('hidden');
        }

        function actualizarPreviewImagenNodo(rutaImagen) {
            const preview = document.getElementById('panel-imagen-preview');
            const img = document.getElementById('panel-imagen-img');
            if (rutaImagen) {
                img.src = '{{ url('/storage') }}/' + rutaImagen;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
                img.src = '';
            }
        }

        function subirImagenNodo(input) {
            if (!nodoSeleccionadoId || !input.files[0]) return;
            const id = nodoSeleccionadoId;
            const estado = document.getElementById('panel-imagen-status');
            estado.textContent = 'Subiendo...';

            const formData = new FormData();
            formData.append('imagen', input.files[0]);

            fetch(`/flujos/nodos/${id}/imagen`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
                body: formData,
            }).then(r => r.json()).then(data => {
                if (!data.ok) { estado.textContent = 'No se pudo subir la foto.'; return; }
                nodosData[id].imagen = data.imagen_url.replace('{{ url('/storage') }}/', '');
                actualizarPreviewImagenNodo(nodosData[id].imagen);
                estado.textContent = 'Foto guardada.';
                input.value = '';
                mostrarGuardado();
            }).catch(() => { estado.textContent = 'No se pudo subir la foto.'; });
        }

        function quitarImagenNodo() {
            if (!nodoSeleccionadoId) return;
            const id = nodoSeleccionadoId;
            fetch(`/flujos/nodos/${id}/imagen`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF },
            }).then(r => r.json()).then(() => {
                nodosData[id].imagen = null;
                actualizarPreviewImagenNodo(null);
                document.getElementById('panel-imagen-status').textContent = 'Foto quitada.';
                mostrarGuardado();
            });
        }

        function cerrarPanel() {
            document.getElementById('flujo-panel').classList.add('translate-x-full');
            document.getElementById('flujo-overlay').classList.add('hidden');
            nodoSeleccionadoId = null;
        }

        function aplicarNodoEnDom(id, titulo, mensaje) {
            const el = canvas.querySelector('.flujo-nodo[data-id="' + id + '"]');
            el.querySelector('p.font-semibold').textContent = titulo;
            el.querySelectorAll('p')[2].textContent = (mensaje || '').length > 90 ? mensaje.slice(0, 90) + '…' : (mensaje || '');
        }

        function guardarNodo() {
            if (!nodoSeleccionadoId) return;
            const id = nodoSeleccionadoId;
            const nodo = nodosData[id];
            const anterior = { titulo: nodo.titulo, mensaje: nodo.mensaje, campo_destino: nodo.campo_destino };

            const titulo = document.getElementById('panel-titulo').value;
            const mensaje = document.getElementById('panel-mensaje').value;
            const campo_destino = document.getElementById('panel-campo').value || null;

            fetch(`/flujos/nodos/${id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ titulo, mensaje, campo_destino }),
            }).then(r => r.json()).then(() => {
                nodo.titulo = titulo;
                nodo.mensaje = mensaje;
                nodo.campo_destino = campo_destino;
                aplicarNodoEnDom(id, titulo, mensaje);
                cerrarPanel();
                mostrarGuardado();

                registrarAccion(async () => {
                    nodo.titulo = anterior.titulo;
                    nodo.mensaje = anterior.mensaje;
                    nodo.campo_destino = anterior.campo_destino;
                    aplicarNodoEnDom(id, anterior.titulo, anterior.mensaje);
                    await fetch(`/flujos/nodos/${id}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify(anterior),
                    });
                });
            });
        }

        // --- Crear / eliminar nodos ---
        const BADGES = { pregunta_texto: 'Pregunta (texto)', pregunta_foto: 'Pregunta (foto)', mensaje_final: 'Mensaje final' };
        const COLORES = {
            pregunta_texto: 'border-[#9c0720]/25 bg-[#9c0720]/10', pregunta_foto: 'border-[#9c0720]/25 bg-[#9c0720]/10',
            mensaje_final: 'border-gray-200 bg-gray-50',
        };

        function crearElementoNodo(nodo) {
            const div = document.createElement('div');
            div.className = `flujo-nodo absolute w-36 h-36 rounded-xl border-2 shadow-sm cursor-move select-none flex flex-col ${COLORES[nodo.tipo]}`;
            div.dataset.id = nodo.id;
            div.style.left = nodo.pos_x + 'px';
            div.style.top = nodo.pos_y + 'px';
            div.style.zIndex = 2;
            div.innerHTML = `
                <div class="px-2.5 py-2 flex-1 min-h-0 flex flex-col">
                    <p class="text-[9px] font-semibold uppercase tracking-wide text-gray-500 leading-tight">${BADGES[nodo.tipo]}</p>
                    <p class="text-xs font-semibold text-gray-900 mt-1 leading-tight line-clamp-2"></p>
                    <p class="text-[10px] text-gray-500 mt-1 leading-snug overflow-hidden flex-1"></p>
                </div>
                <button type="button" class="flujo-eliminar absolute -top-2 -right-2 h-5 w-5 rounded-full bg-white border border-gray-200 text-gray-400 hover:text-red-600 hover:border-red-200 text-xs flex items-center justify-center">✕</button>
                <div class="flujo-puerto-entrada absolute -left-2 top-1/2 -translate-y-1/2 h-3 w-3 rounded-full bg-gray-300 border-2 border-white"></div>
                <div class="flujo-puerto-salida absolute -right-2 top-1/2 -translate-y-1/2 h-3 w-3 rounded-full bg-[#9c0720]/70 border-2 border-white cursor-crosshair"></div>
            `;
            div.querySelectorAll('p')[1].textContent = nodo.titulo;
            div.querySelectorAll('p')[2].textContent = (nodo.mensaje || '').length > 90 ? nodo.mensaje.slice(0, 90) + '…' : (nodo.mensaje || '');
            div.querySelector('.flujo-eliminar').onclick = () => eliminarNodo(nodo.id);
            return div;
        }

        function crearNodo(tipo) {
            const titulo = prompt('Nombre de este paso:');
            if (!titulo) return;

            const pos_x = scrollBox.scrollLeft + 60;
            const pos_y = scrollBox.scrollTop + 60;

            fetch('/flujos/nodos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ tipo, titulo, pos_x, pos_y }),
            }).then(r => r.json()).then(data => {
                nodosData[data.nodo.id] = data.nodo;
                canvas.appendChild(crearElementoNodo(data.nodo));
                mostrarGuardado();
                actualizarAvisos();

                registrarAccion(async () => {
                    delete nodosData[data.nodo.id];
                    canvas.querySelector('.flujo-nodo[data-id="' + data.nodo.id + '"]')?.remove();
                    conexiones = conexiones.filter(c => c.nodo_origen_id !== data.nodo.id && c.nodo_destino_id !== data.nodo.id);
                    redibujarConexiones();
                    actualizarAvisos();
                    await fetch(`/flujos/nodos/${data.nodo.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } });
                });
            });
        }

        function eliminarNodo(id) {
            if (!confirm('¿Eliminar este paso? También se van a borrar sus conexiones.')) return;
            fetch(`/flujos/nodos/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF },
            }).then(r => r.json()).then(data => {
                if (data.ok) {
                    canvas.querySelector('.flujo-nodo[data-id="' + id + '"]')?.remove();
                    conexiones = conexiones.filter(c => c.nodo_origen_id !== id && c.nodo_destino_id !== id);
                    redibujarConexiones();
                    actualizarAvisos();
                    mostrarGuardado();
                } else {
                    alert(data.error);
                }
            });
        }

        function eliminarConexion(id) {
            if (!confirm('¿Eliminar esta conexión?')) return;
            const cx = conexiones.find(c => c.id === id);
            fetch(`/flujos/conexiones/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF },
            }).then(r => r.json()).then(() => {
                conexiones = conexiones.filter(c => c.id !== id);
                redibujarConexiones();
                actualizarAvisos();
                mostrarGuardado();

                if (cx) {
                    registrarAccion(async () => {
                        const r = await fetch('/flujos/conexiones', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                            body: JSON.stringify({ nodo_origen_id: cx.nodo_origen_id, nodo_destino_id: cx.nodo_destino_id, valor: cx.valor, etiqueta: cx.etiqueta }),
                        });
                        const data = await r.json();
                        conexiones.push(data.conexion);
                        redibujarConexiones();
                        actualizarAvisos();
                    });
                }
            });
        }

        // --- Ordenar automáticamente (cuadrícula por niveles, siguiendo las conexiones) ---
        function ordenarAutomaticamente() {
            const posicionesAnteriores = Object.keys(nodosData).map(Number).map(id => {
                const el = canvas.querySelector('.flujo-nodo[data-id="' + id + '"]');
                return { id, pos_x: parseInt(el.style.left), pos_y: parseInt(el.style.top) };
            });

            const ids = Object.keys(nodosData).map(Number);
            const nivelDe = {};
            const inicio = ids.find(id => nodosData[id].tipo === 'menu_inicial') ?? ids[0];

            let frontera = [inicio];
            nivelDe[inicio] = 0;
            let nivel = 0;
            while (frontera.length) {
                const siguienteFrontera = [];
                frontera.forEach(id => {
                    conexiones.filter(c => c.nodo_origen_id === id).forEach(c => {
                        if (!(c.nodo_destino_id in nivelDe)) {
                            nivelDe[c.nodo_destino_id] = nivel + 1;
                            siguienteFrontera.push(c.nodo_destino_id);
                        }
                    });
                });
                frontera = siguienteFrontera;
                nivel++;
            }

            // Cualquier nodo que quedó sin conexión entrante se ubica al final.
            ids.forEach(id => { if (!(id in nivelDe)) nivelDe[id] = nivel; });

            const porNivel = {};
            ids.forEach(id => {
                const n = nivelDe[id];
                (porNivel[n] ??= []).push(id);
            });

            const ESPACIO_X = 220, ESPACIO_Y = 180, MARGEN = 40;
            const actualizaciones = [];

            Object.keys(porNivel).sort((a, b) => a - b).forEach(n => {
                porNivel[n].forEach((id, i) => {
                    const x = MARGEN + n * ESPACIO_X;
                    const y = MARGEN + i * ESPACIO_Y;
                    const el = canvas.querySelector('.flujo-nodo[data-id="' + id + '"]');
                    if (el) { el.style.left = x + 'px'; el.style.top = y + 'px'; }
                    actualizaciones.push({ id, pos_x: x, pos_y: y });
                });
            });

            redibujarConexiones();
            actualizarAvisos();
            Promise.all(actualizaciones.map(u => fetch(`/flujos/nodos/${u.id}/mover`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ pos_x: u.pos_x, pos_y: u.pos_y }),
            }))).then(() => mostrarGuardado());

            registrarAccion(async () => {
                posicionesAnteriores.forEach(p => {
                    const el = canvas.querySelector('.flujo-nodo[data-id="' + p.id + '"]');
                    if (el) { el.style.left = p.pos_x + 'px'; el.style.top = p.pos_y + 'px'; }
                });
                redibujarConexiones();
                await Promise.all(posicionesAnteriores.map(p => fetch(`/flujos/nodos/${p.id}/mover`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ pos_x: p.pos_x, pos_y: p.pos_y }),
                })));
            });
        }

        // --- Avisos de cuadros sin salida (el bot se quedaría "mudo" ahí) ---
        // Algunos tipos de nodo son terminales A PROPÓSITO (la solicitud queda
        // esperando revisión manual del admin, o son plantillas que el admin
        // dispara desde el panel de Solicitudes) y no necesitan conexión de salida.
        const TIPOS_TERMINALES_OK = ['crear_solicitud', 'esperar_comprobante_prenda', 'plantilla_admin'];
        const CLAVES_TERMINALES_OK = ['comprobante_recibido'];

        function actualizarAvisos() {
            document.querySelectorAll('.flujo-nodo').forEach(el => {
                const id = parseInt(el.dataset.id);
                const nodo = nodosData[id];
                const esTerminalOk = nodo && (TIPOS_TERMINALES_OK.includes(nodo.tipo) || CLAVES_TERMINALES_OK.includes(nodo.clave));
                const tieneSalida = esTerminalOk || conexiones.some(c => c.nodo_origen_id === id);
                el.classList.toggle('flujo-nodo-sin-salida', !tieneSalida);
                el.title = tieneSalida ? '' : 'Este paso no tiene ninguna conexión de salida: si un cliente llega acá, la conversación se corta.';
            });
        }

        // --- Restaurar flujo original ---
        function restaurarFlujo() {
            if (!confirm('Esto borra TODOS los cuadros y conexiones que hayas creado o modificado, y vuelve a dejar el flujo original de reservas. ¿Seguro que querés continuar?')) return;
            fetch('/flujos/restaurar', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
            }).then(r => r.json()).then(() => location.reload());
        }

        // --- Probar flujo (simulador de chat) ---
        function agregarMensajePrueba(mensaje, esCliente) {
            const texto = typeof mensaje === 'string' ? mensaje : mensaje.texto;
            const imagen = typeof mensaje === 'string' ? null : mensaje.imagen;

            const chat = document.getElementById('prueba-chat');
            const burbuja = document.createElement('div');
            burbuja.className = esCliente
                ? 'ml-auto max-w-[85%] bg-[#9c0720] text-white rounded-2xl rounded-br-sm px-3.5 py-2 text-sm whitespace-pre-wrap'
                : 'mr-auto max-w-[85%] bg-white border border-gray-200 text-gray-900 rounded-2xl rounded-bl-sm px-3.5 py-2 text-sm whitespace-pre-wrap';

            if (imagen) {
                const img = document.createElement('img');
                img.src = imagen;
                img.className = 'rounded-lg mb-1.5 max-w-full max-h-40 object-cover';
                burbuja.appendChild(img);
            }
            const p = document.createElement('p');
            p.textContent = texto;
            burbuja.appendChild(p);

            chat.appendChild(burbuja);
            chat.scrollTop = chat.scrollHeight;
        }

        function abrirPrueba() {
            document.getElementById('prueba-modal').classList.remove('hidden');
            document.getElementById('prueba-modal').classList.add('flex');
            if (!document.getElementById('prueba-chat').children.length) {
                enviarMensajePruebaAlServidor('');
            }
            document.getElementById('prueba-input').focus();
        }

        function cerrarPrueba() {
            document.getElementById('prueba-modal').classList.add('hidden');
            document.getElementById('prueba-modal').classList.remove('flex');
        }

        function reiniciarPrueba() {
            document.getElementById('prueba-chat').innerHTML = '';
            fetch('/flujos/probar/reiniciar', { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF } })
                .then(() => enviarMensajePruebaAlServidor(''));
        }

        function enviarMensajePruebaAlServidor(mensaje) {
            return fetch('/flujos/probar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ mensaje }),
            }).then(r => r.json()).then(data => {
                (data.mensajes || []).forEach(m => agregarMensajePrueba(m, false));
            });
        }

        function enviarPrueba() {
            const input = document.getElementById('prueba-input');
            const texto = input.value.trim();
            if (!texto) return;
            agregarMensajePrueba(texto, true);
            input.value = '';
            enviarMensajePruebaAlServidor(texto);
        }

        document.getElementById('prueba-input').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') enviarPrueba();
        });

        redibujarConexiones();
        actualizarAvisos();
    </script>
</x-app-layout>
